<?php
require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/utils.php';

// Despacha según el transporte configurado en EMAIL_PROVIDER (emailjs | smtp)
function sendEmail($email) {
    $provider = strtolower(getenv('EMAIL_PROVIDER') ?: 'emailjs');
    if ($provider === 'smtp') {
        $result = sendSmtp($email);
        if (json_decode($result, true)['status']) {
            return $result;
        }
        Utils::log('email_fallback', 'SMTP falló, reintentando con EmailJS: ' . json_decode($result, true)['text']);
        return sendEmailJS($email);
    }
    return sendEmailJS($email);
}

// Transporte EmailJS (API REST, cURL). Fallback por defecto.
function sendEmailJS($email) {
    $api_url = getenv('EMAILJS_API_URL') ?: 'https://api.emailjs.com/api/v1.0/email/send';
    $timeout = (int) getenv('EMAILJS_CURL_TIMEOUT') ?: 10;

    if ($email['action'] === 'send_user') {
        $templateId = getenv('EMAILJS_TEMPLATE_ID_USER');
        $templateParams = [
            'to_email' => $email['to'],
            'from_name' => $email['fromNameParam'] ?? $email['fromName'],
            'message_html' => $email['html'],
            'message_text' => $email['text'],
        ];
    } else {
        $templateId = getenv('EMAILJS_TEMPLATE_ID_NOTIFICATION');
        $templateParams = [
            'to_email' => $email['to'],
            'from_email' => $email['fromEmailParam'] ?? $email['from'],
            'subject' => $email['subject'],
            'message_html' => $email['html'],
            'message_text' => $email['text'],
        ];
    }

    $payload = [
        'service_id' => getenv('EMAILJS_SERVICE_ID'),
        'template_id' => $templateId,
        'user_id' => getenv('USER_ID_EMAILJS'),
        'template_params' => $templateParams,
    ];

    $privateKey = getenv('EMAILJS_PRIVATE_KEY');
    if ($privateKey !== '') {
        $payload['accessToken'] = $privateKey;
    }

    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return json_encode(['status' => true, 'text' => '']);
    }

    return json_encode(['status' => false, 'text' => 'Error al enviar el correo.']);
}

// Transporte SMTP (Gmail u otro) sin librerías externas: sockets puros de PHP.
function sendSmtp($email) {
    $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $port = (int) (getenv('SMTP_PORT') ?: 465);
    $secure = strtolower(getenv('SMTP_SECURE') ?: 'ssl');
    $user = getenv('SMTP_USER') ?: '';
    $pass = getenv('SMTP_PASS') ?: '';
    $timeout = (int) getenv('EMAILJS_CURL_TIMEOUT') ?: 10;

    if ($user === '' || $pass === '') {
        Utils::log('smtp_error', 'SMTP no configurado (SMTP_USER/SMTP_PASS vacíos)');
        return json_encode(['status' => false, 'text' => 'SMTP no configurado.']);
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $conn = @stream_socket_client($remote, $errno, $errstr, $timeout);
    if (!$conn) {
        Utils::log('smtp_error', "connect $host:$port: $errstr");
        return json_encode(['status' => false, 'text' => 'Error al conectar con el servidor de correo.']);
    }
    stream_set_timeout($conn, $timeout);

    $fail = function ($where, $resp = '') use ($conn, $host, $email) {
        Utils::log('smtp_error', "$where | $host | " . trim($resp));
        @fwrite($conn, "QUIT\r\n");
        @fclose($conn);
        return json_encode(['status' => false, 'text' => 'Error al enviar el correo.']);
    };

    $resp = smtpRead($conn);
    if (strpos($resp, '220') !== 0) return $fail('banner', $resp);

    smtpWrite($conn, 'EHLO freepalestine.local');
    $resp = smtpRead($conn);
    if (strpos($resp, '250') !== 0) return $fail('ehlo', $resp);

    if ($secure === 'tls') {
        smtpWrite($conn, 'STARTTLS');
        $resp = smtpRead($conn);
        if (strpos($resp, '220') !== 0) return $fail('starttls', $resp);
        $crypto = stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto) return $fail('tls_handshake', '');
        smtpWrite($conn, 'EHLO freepalestine.local');
        $resp = smtpRead($conn);
        if (strpos($resp, '250') !== 0) return $fail('ehlo_tls', $resp);
    }

    smtpWrite($conn, 'AUTH LOGIN');
    $resp = smtpRead($conn);
    if (strpos($resp, '334') !== 0) return $fail('auth', $resp);
    smtpWrite($conn, base64_encode($user));
    $resp = smtpRead($conn);
    if (strpos($resp, '334') !== 0) return $fail('auth_user', $resp);
    smtpWrite($conn, base64_encode($pass));
    $resp = smtpRead($conn);
    if (strpos($resp, '235') !== 0) {
        Utils::log('smtp_auth_fail', "User: $user | " . trim($resp));
        @fwrite($conn, "QUIT\r\n");
        @fclose($conn);
        return json_encode(['status' => false, 'text' => 'Credenciales SMTP incorrectas.']);
    }

    smtpWrite($conn, 'MAIL FROM:<' . $email['from'] . '>');
    $resp = smtpRead($conn);
    if (strpos($resp, '250') !== 0) return $fail('mail_from', $resp);

    smtpWrite($conn, 'RCPT TO:<' . $email['to'] . '>');
    $resp = smtpRead($conn);
    if (strpos($resp, '250') !== 0) return $fail('rcpt_to', $resp);

    smtpWrite($conn, 'DATA');
    $resp = smtpRead($conn);
    if (strpos($resp, '354') !== 0) return $fail('data', $resp);

    $boundary = 'b' . bin2hex(random_bytes(8));
    $headers = 'From: ' . encodeHeader($email['fromName']) . ' <' . $email['from'] . '>' . "\r\n";
    $headers .= 'To: <' . $email['to'] . '>' . "\r\n";
    $headers .= 'Subject: ' . encodeHeader($email['subject']) . "\r\n";
    if (!empty($email['replyTo'])) {
        $headers .= 'Reply-To: ' . encodeHeader($email['replyToName'] ?? '') . ' <' . $email['replyTo'] . '>' . "\r\n";
    }
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n";

    $body = '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($email['text']), 76, "\r\n") . "\r\n";
    $body .= '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($email['html']), 76, "\r\n") . "\r\n";
    $body .= '--' . $boundary . "--\r\n";

    fwrite($conn, $headers . "\r\n" . $body . "\r\n.\r\n");
    $resp = smtpRead($conn);
    if (strpos($resp, '250') !== 0) return $fail('data_end', $resp);

    smtpWrite($conn, 'QUIT');
    @fclose($conn);
    Utils::log('smtp_sent', 'To: ' . $email['to'] . ', Subject: ' . $email['subject']);
    return json_encode(['status' => true, 'text' => '']);
}

function smtpWrite($conn, $data) {
    fwrite($conn, $data . "\r\n");
}

function smtpRead($conn) {
    $response = '';
    while (($line = fgets($conn, 512)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function encodeHeader($str) {
    if (preg_match('/[^\x20-\x7E]/', $str)) {
        return '=?UTF-8?B?' . base64_encode($str) . '?=';
    }
    return $str;
}
