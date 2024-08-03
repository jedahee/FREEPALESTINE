<?php

// La clase Utils contiene métodos variados que son necesarios para el desarrollo de la web
class Utils {
    public static function get_base_url() {
        // Obtener el esquema (http o https)
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        
        // Obtener el nombre del servidor
        $host = $_SERVER['HTTP_HOST'];
        
        // Obtener el directorio de la ruta actual
        $request_uri = $_SERVER['REQUEST_URI'];
        $base_dir = '/freepalestine/'; // Cambiar /freepalestine/ si el directorio base es diferente
        
        // Construir la URL base
        $base_url = $scheme . "://" . $host . $base_dir;
        
        return $base_url;
    }

    // Leer y decodificar el archivo JSON
    public static function readJsonFile($file) {
        if (file_exists($file)) {
            $json = file_get_contents($file);
            return json_decode($json, true);
        }
        return [];
    }

    // Codificar y escribir en el archivo JSON
    public static function writeJsonFile($file, $data) {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($file, $json);
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

    // Comprueba si existe el email en las firmas registradas, si es así, se borra (en ambos archivos JSON).
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

    // Comprueba si el email del usuario existe en las firmas registradas
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
}
?>