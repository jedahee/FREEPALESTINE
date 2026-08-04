<?php
class Utils {
    public static function get_base_url() {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";

        $allowed_hosts = explode(',', getenv('ALLOWED_HOSTS') ?: 'localhost,127.0.0.1');
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $hostname = parse_url("http://$host", PHP_URL_HOST) ?: $host;

        if (!in_array($hostname, $allowed_hosts) && !in_array($host, $allowed_hosts)) {
            $host = 'localhost';
        }

        $base_url = $scheme . "://" . $host;

        return $base_url;
    }

    public static function log($event, $details = '') {
        $log_dir = __DIR__ . '/logs';
        if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
        $file = $log_dir . '/security-' . date('Y-m') . '.log';

        $files = glob($log_dir . '/security-*.log');
        if ($files) {
            usort($files, function ($a, $b) { return filemtime($a) - filemtime($b); });
            $total_size = 0;
            foreach ($files as $f) $total_size += filesize($f);
            $max_size = (int) getenv('LOG_MAX_TOTAL_SIZE') ?: 104857600;
            while ($total_size > $max_size && count($files) > 1) {
                $oldest = array_shift($files);
                $total_size -= filesize($oldest);
                unlink($oldest);
            }
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $event;
        if ($details) $line .= ' | ' . $details;
        $line .= PHP_EOL;
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function readJsonFile($file) {
        $max_size = (int) getenv('JSON_MAX_READ_SIZE') ?: 10485760;
        if (file_exists($file) && filesize($file) < $max_size) {
            $fh = fopen($file, 'r');
            if ($fh) {
                flock($fh, LOCK_SH);
                $json = stream_get_contents($fh);
                flock($fh, LOCK_UN);
                fclose($fh);
                return json_decode($json, true);
            }
        }
        return [];
    }

    public static function writeJsonFile($file, $data) {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $fh = fopen($file, 'w');
        if ($fh) {
            flock($fh, LOCK_EX);
            fwrite($fh, $json);
            fflush($fh);
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    // Función para desencriptar datos
    public static function decrypt_data($data, $key, $cipher_method) {
        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length($cipher_method);
        $iv = substr($data, 0, $iv_length);
        $encrypted_data = substr($data, $iv_length);
        $decrypted = openssl_decrypt($encrypted_data, $cipher_method, $key, 0, $iv);
        return $decrypted;
    }

    public static function deleteExistingSignature($signatures, $email, $file) {
        if (!empty($signatures)) {
            foreach ($signatures as $key => $signature) { 
                $decrypted_signature = self::decrypt_data($signature, $GLOBALS['encryption_key'], $GLOBALS['cipher_method']);
                if ($decrypted_signature) {
                    $data = json_decode($decrypted_signature, true);
                    if ($data['email'] === $email) {
                        unset($signatures[$key]); 
                        self::writeJsonFile($file, $signatures); 
                        return true;
                    }
                }
            }
        }

        return false;
    }


    public static function checkExistingSignature($signatures, $email) {
        if (!empty($signatures)){
            foreach($signatures as $signature) {
                $decrypted_signature = self::decrypt_data($signature, $GLOBALS['encryption_key'], $GLOBALS['cipher_method']);
                if ($decrypted_signature) {
                    $data = json_decode($decrypted_signature, true);
                    if ($data['email'] === $email) return true; 
                }            
            }
        }
        return false;
    }

    // Función para encriptar datos
    public static function encrypt_data($data, $key, $cipher_method) {
        $iv_length = openssl_cipher_iv_length($cipher_method);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, $cipher_method, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    // Escapar para HTML (alias corto)
    public static function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    // Normalizar input: trim, quitar caracteres de control y limitar longitud
    public static function normalize($value, $max_len = 255) {
        $value = (string) $value;
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim($value);
        if (function_exists('mb_substr')) {
            $value = mb_substr($value, 0, $max_len, 'UTF-8');
        } else {
            $value = substr($value, 0, $max_len);
        }
        return $value;
    }

    // IP del cliente (REMOTE_ADDR por defecto; permite confiar en un header de proxy vía env)
    public static function get_client_ip() {
        $header = getenv('TRUST_PROXY_IP_HEADER');
        if ($header) {
            $value = trim($_SERVER[$header] ?? '');
            if ($value !== '') {
                $parts = explode(',', $value);
                $ip = trim($parts[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    // Sesión PHP endurecida (Secure/HttpOnly/SameSite) antes de session_start()
    public static function start_secure_session() {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Cabeceras de seguridad para respuestas JSON/descargas
    public static function send_security_headers() {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store');
    }

    // Buscar código pendiente por email + randomString. Devuelve el índice o null.
    public static function findPendingCode($codes, $email, $randomString) {
        if (!empty($codes)) {
            foreach ($codes as $index => $code) {
                $decrypted = self::decrypt_data($code, $GLOBALS['encryption_key'], $GLOBALS['cipher_method']);
                if ($decrypted) {
                    $data = json_decode($decrypted, true);
                    if (isset($data['email'], $data['randomString'])
                        && $data['email'] === $email
                        && hash_equals((string) $data['randomString'], (string) $randomString)) {
                        return $index;
                    }
                }
            }
        }
        return null;
    }

    // Buscar firma confirmada por email + randomString (token enviado por correo).
    // Permite cancelar una firma ya confirmada. Devuelve el índice o null.
    public static function findSignatureByToken($signatures, $email, $randomString) {
        if (!empty($signatures)) {
            foreach ($signatures as $index => $signature) {
                $decrypted = self::decrypt_data($signature, $GLOBALS['encryption_key'], $GLOBALS['cipher_method']);
                if ($decrypted) {
                    $data = json_decode($decrypted, true);
                    if (isset($data['email'], $data['randomString'])
                        && $data['email'] === $email
                        && hash_equals((string) $data['randomString'], (string) $randomString)) {
                        return $index;
                    }
                }
            }
        }
        return null;
    }

    // ¿Ya existe un código pendiente para este email? Evita solicitudes duplicadas.
    public static function hasPendingCode($codes, $email) {
        if (!empty($codes)) {
            foreach ($codes as $code) {
                $decrypted = self::decrypt_data($code, $GLOBALS['encryption_key'], $GLOBALS['cipher_method']);
                if ($decrypted) {
                    $data = json_decode($decrypted, true);
                    if (isset($data['email']) && $data['email'] === $email) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    // Renderizar una plantilla de correo desde archivo con tokens {{clave}}
    public static function render_template($file, $vars) {
        if (!is_file($file)) {
            return '';
        }
        $template = file_get_contents($file);
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }
        return $template;
    }
}
?>