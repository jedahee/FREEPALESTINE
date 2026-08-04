<?php
    require_once 'load_env.php';
    require_once 'utils.php';

    Utils::start_secure_session();
    Utils::send_security_headers();

    define('MAX_NAME_LEN', 100);
    define('MAX_EMAIL_LEN', 254);
    define('MAX_RANDOM_LEN', 64);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $submitted_token = $input['csrf_token'] ?? '';
        if (empty($submitted_token) || $submitted_token !== ($_SESSION['csrf_token'] ?? '')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'text' => 'Solicitud inválida.']);
            exit;
        }
    }

    $ip = Utils::get_client_ip();
    $rate_limit_file = sys_get_temp_dir() . '/sig_rate_' . md5($ip);
    $now = time();
    $window = (int) getenv('SIG_RATE_LIMIT_WINDOW') ?: 60;
    $max_requests = (int) getenv('SIG_RATE_LIMIT_MAX') ?: 10;

    if (file_exists($rate_limit_file)) {
        $rl = json_decode(file_get_contents($rate_limit_file), true);
        $timestamps = array_filter($rl['timestamps'] ?? [], function ($t) use ($now, $window) {
            return $t > $now - $window;
        });
    } else {
        $timestamps = [];
    }

    if (count($timestamps) >= $max_requests) {
        http_response_code(429);
        header('Content-Type: application/json');
        Utils::log('rate_limit', "IP: $ip, endpoint: save_signature");
        echo json_encode(['status' => false, 'text' => 'Demasiadas solicitudes. Intente más tarde.']);
        exit;
    }

    $timestamps[] = $now;
    file_put_contents($rate_limit_file, json_encode(['timestamps' => $timestamps]), LOCK_EX);

    $file = getenv('FILENAME_JSON');
    $file_code = getenv('FILENAME_JSON_CODE');

    $encryption_key = getenv('ENCRYPTION_KEY');
    $cipher_method = getenv('CIPHER_METHOD');
    $GLOBALS['encryption_key'] = $encryption_key;
    $GLOBALS['cipher_method'] = $cipher_method;
    $baseUrl = Utils::get_base_url();
    $codes = Utils::readJsonFile($file_code);
    $signatures = Utils::readJsonFile($file);

    // Procesar la solicitud POST
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        $name = Utils::normalize($_GET['name'] ?? '', MAX_NAME_LEN);
        $email = Utils::normalize($_GET['email'] ?? '', MAX_EMAIL_LEN);
        $randomString = Utils::normalize($_GET['randomString'] ?? '', MAX_RANDOM_LEN);

        switch ($action) {
            case 'Sign':
                // Doble opt-in real: se valida email + randomString contra codes.json
                $validCode = $name !== ''
                    && filter_var($email, FILTER_VALIDATE_EMAIL)
                    && preg_match('/^[A-Za-z0-9]+$/', $randomString)
                    && Utils::findPendingCode($codes, $email, $randomString) !== null;

                if ($validCode) {
                    $data_to_encrypt = json_encode([
                        'name' => $name,
                        'email' => $email,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'randomString' => $randomString,
                    ]);
                    $encrypted_data = Utils::encrypt_data($data_to_encrypt, $encryption_key, $cipher_method);
                    $signatures[] = $encrypted_data;
                    Utils::writeJsonFile($file, $signatures);

                    // Borrar el código pendiente para evitar duplicados por re-confirmación
                    $index = Utils::findPendingCode($codes, $email, $randomString);
                    if ($index !== null) {
                        unset($codes[$index]);
                        Utils::writeJsonFile($file_code, array_values($codes));
                    }
                    Utils::log('sign_confirm', "Email: $email, Name: $name");
                    header("Location: ".$baseUrl."?sign=true");
                } else {
                    Utils::log('sign_confirm_rejected', "Email: $email");
                    header("Location: ".$baseUrl."?sign=error");
                }
                break;

            case 'CancelSign':
                $valid = filter_var($email, FILTER_VALIDATE_EMAIL)
                    && preg_match('/^[A-Za-z0-9]+$/', $randomString);

                if (!$valid) {
                    Utils::log('sign_cancel_rejected', "Email: $email");
                    header("Location: ".$baseUrl."?sign=error");
                    break;
                }

                // 1) Código pendiente (firma sin confirmar)
                $pendingIndex = Utils::findPendingCode($codes, $email, $randomString);
                // 2) Firma ya confirmada con el mismo token del correo
                $sigIndex = Utils::findSignatureByToken($signatures, $email, $randomString);

                if ($pendingIndex !== null) {
                    unset($codes[$pendingIndex]);
                    Utils::writeJsonFile($file_code, array_values($codes));
                    Utils::deleteExistingSignature($signatures, $email, $file);
                    Utils::log('sign_cancel', "Email: $email (pendiente)");
                    header("Location: ".$baseUrl."?sign=false");
                } elseif ($sigIndex !== null) {
                    unset($signatures[$sigIndex]);
                    Utils::writeJsonFile($file, array_values($signatures));
                    Utils::log('sign_cancel', "Email: $email (confirmada)");
                    header("Location: ".$baseUrl."?sign=false");
                } else {
                    Utils::log('sign_cancel_rejected', "Email: $email");
                    header("Location: ".$baseUrl."?sign=error");
                }
                break;

            default:
                header("Location: ".$baseUrl);
                break;
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $action = $input['action'] ?? '';
        $name = Utils::normalize($input['name'] ?? '', MAX_NAME_LEN);
        $email = Utils::normalize($input['email'] ?? '', MAX_EMAIL_LEN);
        $randomString = Utils::normalize($input['randomString'] ?? '', MAX_RANDOM_LEN);

        switch ($action) {
            case 'SaveString':
                $signatures = Utils::readJsonFile($file);
                $codes = Utils::readJsonFile($file_code);

                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Za-z0-9]+$/', $randomString)) {
                    Utils::log('sign_invalid', "Email: $email");
                    echo json_encode(["status" => false, "text" => "Datos de firma inválidos"]);
                    break;
                }

                if (Utils::hasPendingCode($codes, $email)) {
                    Utils::log('sign_duplicate_pending', "Email: $email");
                    echo json_encode(["status" => false, "text" => "Ya tienes una firma pendiente de confirmar. Revisa tu correo."]);
                    break;
                }

                if (!Utils::checkExistingSignature($signatures, $email)) {
                    $data_to_encrypt = json_encode(['name' => $name, 'email' => $email, 'randomString' => $randomString]);
                    $codes[] = Utils::encrypt_data($data_to_encrypt, $encryption_key, $cipher_method);
                    Utils::writeJsonFile($file_code, $codes);
                    Utils::log('sign_requested', "Email: $email");

                    echo json_encode(["status" => true, "text" => ""]);
                } else {
                    Utils::log('sign_duplicate', "Email: $email");
                    echo json_encode(["status" => false, "text" => "Ya has firmado con este nombre de correo electrónico"]);
                }

                break;

            default:
                http_response_code(400);
                echo json_encode(["status" => false, "text" => "Petición inválida"]);
                break;
        }
    } else {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(["status" => false, "text" => "Petición inválida"]);
    }
