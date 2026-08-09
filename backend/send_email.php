<?php
require_once 'load_env.php';
require_once 'utils.php';
require_once 'i18n.php';
require_once 'email_transport.php';

i18n_init('es'); // fallback; se re-inicializa con el idioma real tras parsear el body

Utils::start_secure_session();
Utils::send_security_headers();

define('MAX_NAME_LEN', 100);
define('MAX_EMAIL_LEN', 254);
define('MAX_SUBJECT_LEN', 150);
define('MAX_MSG_LEN', 3000);

$allowed_origins = explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'https://freepalestine.es');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Content-Type: application/json');

$ip = Utils::get_client_ip();
$rate_limit_file = sys_get_temp_dir() . '/email_rate_' . md5($ip);

$now = time();
$window = (int) getenv('EMAIL_RATE_LIMIT_WINDOW') ?: 60;
$max_requests = (int) getenv('EMAIL_RATE_LIMIT_MAX') ?: 5;

if (file_exists($rate_limit_file)) {
    $data = json_decode(file_get_contents($rate_limit_file), true);
    $timestamps = array_filter($data['timestamps'] ?? [], function ($t) use ($now, $window) {
        return $t > $now - $window;
    });
} else {
    $timestamps = [];
}

if (count($timestamps) >= $max_requests) {
    http_response_code(429);
    Utils::log('rate_limit', "IP: $ip, endpoint: send_email");
    echo json_encode(['status' => false, 'text' => t('backend.too_many_requests')]);
    exit;
}

$timestamps[] = $now;
file_put_contents($rate_limit_file, json_encode(['timestamps' => $timestamps]), LOCK_EX);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'text' => t('backend.method_not_allowed')]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Idioma del usuario que firma/contacta (para correos y textos de error)
$lang = isset($input['lang']) && isset(i18n_langs()[$input['lang']]) ? $input['lang'] : 'es';
i18n_init($lang);

$submitted_token = $input['csrf_token'] ?? '';
if (empty($submitted_token) || $submitted_token !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => false, 'text' => t('backend.invalid_request') . '.']);
    exit;
}

// Honeypot anti-bots: si viene relleno, se responde OK pero no se envía
if (!empty($input['website'] ?? '')) {
    Utils::log('spam_honeypot', "IP: $ip");
    echo json_encode(['status' => true, 'text' => '']);
    exit;
}

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'text' => t('backend.invalid_request')]);
    exit;
}

$fpEmail = getenv('FP_EMAIL');
$templates_dir = __DIR__ . '/templates';

switch ($input['action']) {
    case 'send_user':
        $name = Utils::normalize($input['name'] ?? '', MAX_NAME_LEN);
        $email = Utils::normalize($input['email'] ?? '', MAX_EMAIL_LEN);
        $validateUrl = $input['validateUrl'] ?? '';
        $cancelUrl = $input['cancelUrl'] ?? '';
        $baseUrl = $input['baseUrl'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => false, 'text' => t('backend.invalid_email_name')]);
            exit;
        }

        $lang = current_lang();
        $sigTemplate = $templates_dir . '/email_signature.' . $lang . '.html';
        if (!is_file($sigTemplate)) {
            $sigTemplate = $templates_dir . '/email_signature.html';
        }

        $vars = [
            'greeting_name' => Utils::e($name),
            'validate_url'  => Utils::e($validateUrl),
            'cancel_url'    => Utils::e($cancelUrl),
            'base_url'      => Utils::e($baseUrl),
        ];
        $messageHtml = Utils::render_template($sigTemplate, $vars);
        $messageText = t('email.sign_thanks', ['name' => $name]) . "\n\n"
            . t('email.sign_step_forward') . "\n\n"
            . t('email.sign_confirm_link') . ":\n$validateUrl\n\n"
            . t('email.sign_cancel_link') . ":\n$cancelUrl\n\n"
            . t('email.sign_bye') . "\nFreePalestine";

        $emailData = [
            'action' => 'send_user',
            'to' => $email,
            'from' => getenv('SMTP_FROM') ?: $fpEmail,
            'fromName' => getenv('SMTP_FROM_NAME') ?: 'FreePalestine',
            'subject' => t('email.subject_user'),
            'replyTo' => '',
            'html' => $messageHtml,
            'text' => $messageText,
            'fromNameParam' => $name,
        ];

        $result = sendEmail($emailData);
        Utils::log('email_send_user', "Email: $email, Name: $name, Result: " . (json_decode($result, true)['status'] ? 'OK' : 'FAIL'));
        echo $result;
        break;

    case 'send_notification':
        $email = Utils::normalize($input['email'] ?? '', MAX_EMAIL_LEN);
        $subject = Utils::normalize($input['subject'] ?? '', MAX_SUBJECT_LEN);
        $msg = Utils::normalize($input['msg'] ?? '', MAX_MSG_LEN);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $msg === '') {
            echo json_encode(['status' => false, 'text' => t('backend.invalid_email_subject_msg')]);
            exit;
        }

        $vars = [
            'email'   => Utils::e($email),
            'subject' => Utils::e($subject),
            'msg'     => Utils::e($msg),
        ];
        $messageHtml = Utils::render_template($templates_dir . '/email_notification.html', $vars);
        $messageText = "Nuevo mensaje desde la web FreePalestine.\n\n"
            . "Correo del remitente: $email\n"
            . "Asunto: $subject\n\n"
            . "Mensaje:\n$msg";

        $emailData = [
            'action' => 'send_notification',
            'to' => $fpEmail,
            'from' => getenv('SMTP_FROM') ?: $fpEmail,
            'fromName' => getenv('SMTP_FROM_NAME') ?: 'FreePalestine',
            'subject' => 'Nuevo mensaje desde FreePalestine',
            'replyTo' => $email,
            'replyToName' => $email,
            'html' => $messageHtml,
            'text' => $messageText,
            'fromEmailParam' => $email,
        ];

        $result = sendEmail($emailData);
        Utils::log('email_notification', "From: $email, Result: " . (json_decode($result, true)['status'] ? 'OK' : 'FAIL'));
        echo $result;
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => false, 'text' => t('backend.action_not_recognized')]);
        break;
}

