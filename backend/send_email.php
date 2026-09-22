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

$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
$is_multipart = stripos($content_type, 'multipart/form-data') === 0;
$input = $is_multipart ? $_POST : json_decode(file_get_contents('php://input'), true);

/**
 * Convierte un subconjunto seguro de Markdown a HTML para el correo.
 * Solo emite etiquetas de una lista blanca; todo el resto va escapado,
 * por lo que el contenido del visitante nunca puede inyectar scripts.
 */
function md_esc(string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function md_inline(string $s): string {
    $out = md_esc($s);
    $out = preg_replace('/`([^`]+)`/', '<code>$1</code>', $out);
    $out = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $out);
    $out = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $out);
    $out = preg_replace_callback(
        '/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/',
        function ($m) {
            $label = md_esc($m[1]);
            $url = md_esc($m[2]);
            return '<a href="' . $url . '" rel="noopener noreferrer" target="_blank">' . $label . '</a>';
        },
        $out
    );
    return $out;
}

function md_to_html(string $md): string {
    $lines = preg_split('/\r\n|\r|\n/', (string) $md);
    $out = [];
    $listTag = null;
    $listItems = [];
    $flushList = function () use (&$listTag, &$listItems, &$out) {
        if ($listTag !== null) {
            $out[] = '<' . $listTag . '>' . implode('', $listItems) . '</' . $listTag . '>';
            $listTag = null;
            $listItems = [];
        }
    };
    $n = count($lines);
    for ($i = 0; $i < $n; $i++) {
        $line = $lines[$i];
        if (preg_match('/^(#{1,3})\s+(.*)$/', $line, $h)) {
            $flushList();
            $lvl = strlen($h[1]);
            $out[] = '<h' . $lvl . '>' . md_inline($h[2]) . '</h' . $lvl . '>';
            continue;
        }
        if (preg_match('/^>\s?(.*)$/', $line, $q)) {
            $flushList();
            $out[] = '<blockquote>' . md_inline($q[1]) . '</blockquote>';
            continue;
        }
        $isUl = preg_match('/^[-*+]\s+(.*)$/', $line, $u);
        $isOl = !$isUl && preg_match('/^\d+[.)]\s+(.*)$/', $line, $o);
        if ($isUl || $isOl) {
            $flushList();
            $listTag = $isUl ? 'ul' : 'ol';
            $tokens = [$isUl ? $u[1] : $o[1]];
            while ($i + 1 < $n) {
                $next = $lines[$i + 1];
                $nu = (bool) preg_match('/^[-*+]\s+(.*)$/', $next, $um);
                $no = !$nu && (bool) preg_match('/^\d+[.)]\s+(.*)$/', $next, $om);
                if ($isUl ? !$nu : !$no) {
                    break;
                }
                $tokens[] = $isUl ? $um[1] : $om[1];
                $i++;
            }
            $items = '';
            foreach ($tokens as $tok) {
                $items .= '<li>' . md_inline($tok) . '</li>';
            }
            $out[] = '<' . $listTag . '>' . $items . '</' . $listTag . '>';
            $listTag = null;
            continue;
        }
        $flushList();
        if (trim($line) === '') {
            continue;
        }
        $out[] = '<p>' . md_inline($line) . '</p>';
    }
    $flushList();
    return implode("\n", $out);
}

/** Guarda un adjunto validado en /uploads y devuelve [ruta_relativa, nombre_original]. */
function save_opinion_upload(string $field): ?array {
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $max_bytes = 5 * 1024 * 1024;
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($f['size'] ?? 0) <= 0 || $f['size'] > $max_bytes) {
        http_response_code(400);
        echo json_encode(['status' => false, 'text' => t('backend.file_invalid')]);
        exit;
    }
    $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
    $allowed = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];
    if (!isset($allowed[$ext])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'text' => t('backend.file_invalid')]);
        exit;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    $mime_ok = $mime === $allowed[$ext]
        // Word suele enviar mimes genéricos u octet-stream según el navegador
        || in_array($ext, ['doc', 'docx'], true);
    if (!$mime_ok) {
        http_response_code(400);
        echo json_encode(['status' => false, 'text' => t('backend.file_invalid')]);
        exit;
    }
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $filename)) {
        http_response_code(500);
        echo json_encode(['status' => false, 'text' => t('backend.file_invalid')]);
        exit;
    }
    @chmod($dir . '/' . $filename, 0644);
    return ['uploads/' . $filename, basename($f['name'])];
}

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
        $msgHtml = md_to_html($input['msg'] ?? '');
        $name = Utils::normalize($input['name'] ?? '', MAX_NAME_LEN);
        $image = trim((string) ($input['image'] ?? ''));
        if ($image !== '' && !filter_var($image, FILTER_VALIDATE_URL)) {
            $image = '';
        }
        $author_url = trim((string) ($input['author_url'] ?? ''));
        if ($author_url !== '' && !filter_var($author_url, FILTER_VALIDATE_URL)) {
            $author_url = '';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $msg === '') {
            echo json_encode(['status' => false, 'text' => t('backend.invalid_email_subject_msg')]);
            exit;
        }

        $doc_upload = save_opinion_upload('doc_file');
        $img_upload = save_opinion_upload('image_file');
        $base = rtrim(Utils::get_base_url(), '/');

        $tdLabel = 'padding:8px 0; color:#777777; font-size:13px; font-family:Arial,Helvetica,sans-serif; width:90px; vertical-align:top;';
        $tdValue = 'padding:8px 0; color:#111111; font-size:15px; font-weight:bold; font-family:Arial,Helvetica,sans-serif;';
        $linkStyle = 'color:#d80032;';
        $image_href = $img_upload ? $base . '/' . $img_upload[0] : $image;
        $vars = [
            'email'   => Utils::e($email),
            'subject' => Utils::e($subject),
            'msg'     => $msgHtml,
            'name_block' => $name !== ''
                ? '<tr><td style="' . $tdLabel . '">Nombre</td><td style="' . $tdValue . '">' . Utils::e($name) . '</td></tr>'
                : '',
            'author_url_block' => $author_url !== ''
                ? '<tr><td style="' . $tdLabel . '">Web personal</td><td style="' . $tdValue . '"><a href="' . Utils::e($author_url) . '" style="' . $linkStyle . '">' . Utils::e($author_url) . '</a></td></tr>'
                : '',
            'image_block' => $image_href !== ''
                ? '<tr><td style="' . $tdLabel . '">Imagen</td><td style="' . $tdValue . '"><a href="' . Utils::e($image_href) . '" style="' . $linkStyle . '">' . Utils::e($img_upload ? $img_upload[1] : $image_href) . '</a></td></tr>'
                : '',
            'doc_block' => $doc_upload
                ? '<tr><td style="' . $tdLabel . '">Documento</td><td style="' . $tdValue . '"><a href="' . Utils::e($base . '/' . $doc_upload[0]) . '" style="' . $linkStyle . '">' . Utils::e($doc_upload[1]) . '</a> (' . number_format(($_FILES['doc_file']['size'] ?? 0) / 1048576, 2) . ' MB)</td></tr>'
                : '',
        ];
        $messageHtml = Utils::render_template($templates_dir . '/email_notification.html', $vars);
        $messageText = "Nueva opinión desde la web FreePalestine.\n\n"
            . "Nombre: " . ($name !== '' ? $name : 'Anónimo') . "\n"
            . "Correo del remitente: $email\n"
            . "Asunto: $subject\n\n"
            . "Mensaje:\n$msg";
        if ($author_url !== '') {
            $messageText .= "\n\nWeb personal: $author_url";
        }
        if ($image_href !== '') {
            $messageText .= "\n\nImagen: " . ($img_upload ? $img_upload[1] . " -> " : '') . $image_href;
        }
        if ($doc_upload) {
            $messageText .= "\n\nDocumento adjunto: " . $doc_upload[1] . " -> " . $base . '/' . $doc_upload[0];
        }

        $emailData = [
            'action' => 'send_notification',
            'to' => $fpEmail,
            'from' => getenv('SMTP_FROM') ?: $fpEmail,
            'fromName' => getenv('SMTP_FROM_NAME') ?: 'FreePalestine',
            'subject' => 'Nueva opinión desde FreePalestine',
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

