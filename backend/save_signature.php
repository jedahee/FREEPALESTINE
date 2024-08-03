<?php
    // Archivos requeridos
    require_once 'load_env.php';
    require_once 'utils.php';

    session_start();

    // Ruta de los archivo JSON
    $file = getenv('FILENAME_JSON');
    $file_code = getenv('FILENAME_JSON_CODE');

    // Clave y método de cifrado
    $encryption_key = getenv('ENCRYPTION_KEY');
    $cipher_method = getenv('CIPHER_METHOD');
    
    $baseUrl = Utils::get_base_url();
    
    // Obtener firmas de los archivos
    $codes = Utils::readJsonFile($file_code);
    $signatures = Utils::readJsonFile($file);

    // Procesar la solicitud POST
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        switch ($_GET["action"]) {
            case 'Sign':
                // La acción Sign registra una nueva firma en el archivo json

                // Datos pasados por la URL
                $name = $_GET['name'];
                $email = $_GET['email'];
                $randomString = $_GET['randomString'];

                if (Utils::checkExistingSignature($codes, $email)){
                    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        // Encriptación de datos 
                        $data_to_encrypt = json_encode(['name' => $name, 'email' => $email, 'timestamp' => date('Y-m-d H:i:s')]);
                        $encrypted_data = Utils::encrypt_data($data_to_encrypt, $encryption_key, $cipher_method);
                        $signatures[] = $encrypted_data;
                        Utils::writeJsonFile($file, $signatures); // Añade los datos encriptados

                        header("Location: ".$baseUrl."?sign=true"); // Redirige a la home
                    } else header("Location: ".$baseUrl."?sign=error");
                } else header("Location: ".$baseUrl."?sign=error");
                break;
            case 'CancelSign':
                // Esta acción busca y elimina la firma del archivo JSON
                $email = $_GET['email'];
                $name = $_GET['name'];
                
                if (Utils::deleteExistingSignature($signatures, $email, $file) && Utils::deleteExistingSignature($codes, $email, $file_code))
                    header("Location: ".$baseUrl."?sign=false");
                else
                    header("Location: ".$baseUrl."?sign=error");
                break;
            default:
                header("Location: ".$baseUrl);
                break;
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($input['action']) {
            case 'SaveString':
                // Esta acción guarda la petición de un usuario de firmar.
                $email = $input['email'];
                $name = $input['name'];
                $signatures = Utils::readJsonFile($file);
                $codes = Utils::readJsonFile($file_code);

                // Se comprueba que no se haya firmado ya con este correo
                if (!Utils::checkExistingSignature($signatures, $email)) {
                    $randomString = htmlspecialchars($input['randomString']);
                    $data_to_encrypt = json_encode(['name' => $name, 'email' => $email, 'randomString' => $randomString]);
                    $codes[] = $encrypted_data = Utils::encrypt_data($data_to_encrypt, $encryption_key, $cipher_method);
                    Utils::writeJsonFile($file_code, $codes); // Se añade la firma al archivo JSON que contiene las solicitudes de firma

                    echo json_encode(["status"=>true, "text"=>""]);
                } else
                    echo json_encode(["status"=>false, "text"=>"Ya has firmado con este nombre de correo electrónico"]);
                
                break;
            default:
                header("Location: ".$baseUrl);
                break;
        }
    } else echo json_encode(["status"=>false, "text"=>"Petición inválida"]);

    

?> 