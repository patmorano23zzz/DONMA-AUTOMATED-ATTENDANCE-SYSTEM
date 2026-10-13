<?php
/**
 * Password-reset mail configuration.
 *
 * Configure these values in config/deployment.php or the PHP process environment.
 */
require_once __DIR__ . '/runtime.php';

/**
 * Appends a diagnostic line to assets/cache/mail.log.
 * Never throws — logging must not break the request.
 */
function mail_log(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . rtrim($message) . PHP_EOL;
    $dir  = __DIR__ . '/../assets/cache';
    $file = $dir . '/mail.log';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
        error_log(rtrim($message));
    }
}

function sendPasswordResetEmail(string $recipient, string $name, string $resetUrl): string
{
    $transport = strtolower(trim((string)app_config('MAIL_TRANSPORT', 'smtp')));
    if ($transport === 'brevo') {
        return sendBrevoPasswordResetEmail($recipient, $name, $resetUrl);
    }
    if ($transport !== 'smtp') {
        throw new RuntimeException('MAIL_TRANSPORT must be smtp or brevo.');
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer dependencies are not installed.');
    }
    require_once $autoload;

    $requiredSettings = ['APP_URL', 'SMTP_HOST', 'SMTP_FROM_EMAIL'];
    $settings = [];
    foreach ($requiredSettings as $setting) {
        $value = app_config($setting);
        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException("Missing required mail setting: {$setting}");
        }
        $settings[$setting] = trim($value);
    }

    $appUrl = filter_var($settings['APP_URL'], FILTER_VALIDATE_URL);
    $urlParts = is_string($appUrl) ? parse_url($appUrl) : false;
    $appScheme = is_array($urlParts) ? strtolower((string)($urlParts['scheme'] ?? '')) : '';
    $appHost = is_array($urlParts) ? strtolower((string)($urlParts['host'] ?? '')) : '';
    if (!$appUrl || !in_array($appScheme, ['http', 'https'], true) || $appHost === ''
        || isset($urlParts['user']) || isset($urlParts['pass'])
        || isset($urlParts['query']) || isset($urlParts['fragment'])
        || ($appScheme !== 'https' && !in_array($appHost, ['localhost', '127.0.0.1'], true))) {
        throw new RuntimeException('APP_URL must be a canonical HTTPS URL (HTTP is allowed only for localhost).');
    }

    if (!filter_var($settings['SMTP_FROM_EMAIL'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('SMTP_FROM_EMAIL must be a valid email address.');
    }

    $port = app_config('SMTP_PORT', '587');
    $port = trim((string)$port) === '' ? 587 : filter_var($port, FILTER_VALIDATE_INT);
    if ($port === false || $port < 1 || $port > 65535) {
        throw new RuntimeException('SMTP_PORT must be a valid TCP port.');
    }

    $encryption = strtolower(trim((string)app_config('SMTP_ENCRYPTION', 'tls')));
    if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
        throw new RuntimeException('SMTP_ENCRYPTION must be tls, ssl, or none.');
    }

    $username = trim((string)app_config('SMTP_USERNAME', ''));
    $password = (string)app_config('SMTP_PASSWORD', '');
    if (($username === '') !== ($password === '')) {
        throw new RuntimeException('SMTP_USERNAME and SMTP_PASSWORD must both be configured or both be empty.');
    }

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $settings['SMTP_HOST'];
    $mailer->Port = $port;
    $mailer->SMTPAuth = $username !== '';
    $mailer->Username = $username;
    $mailer->Password = $password;
    $mailer->SMTPSecure = match ($encryption) {
        'tls' => PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
        'ssl' => PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
        default => '',
    };
    $mailer->Timeout = 15;
    $mailer->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
    $mailer->setFrom(
        $settings['SMTP_FROM_EMAIL'],
        trim((string)app_config('SMTP_FROM_NAME', 'DONMA Attendance Tracking System'))
    );
    $mailer->addAddress($recipient, $name);
    $mailer->isHTML(true);
    $mailer->Subject = 'Reset your DONMA ATS password';

    $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mailer->Body = "<p>Hello {$safeName},</p>"
        . '<p>Use the link below to choose a new password. This link expires in 60 minutes '
        . 'and can only be used once.</p>'
        . "<p><a href=\"{$safeUrl}\">Reset your password</a></p>"
        . '<p>If you did not request this change, you can ignore this email.</p>';
    $mailer->AltBody = "Hello {$name},\n\n"
        . "Use this link to choose a new password. It expires in 60 minutes and can only be used once:\n"
        . "{$resetUrl}\n\nIf you did not request this change, you can ignore this email.";
    try {
        $mailer->send();
    } catch (PHPMailer\PHPMailer\Exception $e) {
        throw new RuntimeException(
            'SMTP send failed: ' . $e->getMessage()
            . ' Note: InfinityFree blocks outbound SMTP ports (587/465); on that host set'
            . " mail_transport to 'brevo' in config/deployment.php and use the Brevo HTTP API.",
            0,
            $e
        );
    }
    return $mailer->getLastMessageID();
}

function sendBrevoPasswordResetEmail(string $recipient, string $name, string $resetUrl): string
{
    $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return sendBrevoEmail(
        $recipient,
        $name,
        'Reset your DONMA ATS password',
        "<p>Hello {$safeName},</p>"
            . '<p>Use the link below to choose a new password. This link expires in 60 minutes '
            . 'and can only be used once.</p>'
            . "<p><a href=\"{$safeUrl}\">Reset your password</a></p>"
            . '<p>If you did not request this change, you can ignore this email.</p>',
        "Hello {$name},\n\n"
            . "Use this link to choose a new password. It expires in 60 minutes and can only be used once:\n"
            . "{$resetUrl}\n\nIf you did not request this change, you can ignore this email."
    );
}

function sendBrevoDiagnosticEmail(string $recipient, string $name): string
{
    $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return sendBrevoEmail(
        $recipient,
        $name,
        'DONMA ATS email delivery test',
        "<p>Hello {$safeName},</p><p>This is a test email from the DONMA Attendance Tracking System. "
            . 'Email delivery through Brevo is working.</p>',
        "Hello {$name},\n\nThis is a test email from the DONMA Attendance Tracking System. "
            . 'Email delivery through Brevo is working.'
    );
}

/**
 * POSTs JSON to the Brevo API. Uses cURL when available and falls back to
 * file_get_contents, because some shared hosts (e.g. InfinityFree) restrict
 * allow_url_fopen.
 *
 * @return array{body: ?string, status: int, error: string}
 */
function brevoHttpPost(string $url, string $apiKey, string $payload): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . $apiKey,
                'accept: application/json',
                'content-type: application/json',
            ],
        ]);
        $body   = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);
        return [
            'body'   => is_string($body) && $body !== '' ? $body : null,
            'status' => $status,
            'error'  => $error,
        ];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'api-key: ' . $apiKey . "\r\n"
                . "accept: application/json\r\n"
                . "content-type: application/json\r\n",
            'content' => $payload,
            'timeout' => 20,
            'ignore_errors' => true,
            'follow_location' => 0,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];
    $status = 0;
    foreach ($responseHeaders as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $matches)) {
            $status = (int)$matches[1];
        }
    }

    return [
        'body'   => is_string($body) && $body !== '' ? $body : null,
        'status' => $status,
        'error'  => '',
    ];
}

function sendBrevoEmail(
    string $recipient,
    string $name,
    string $subject,
    string $htmlContent,
    string $textContent
): string {
    $apiKey = app_config('BREVO_API_KEY');
    $fromEmail = app_config('SMTP_FROM_EMAIL');
    if (!is_string($apiKey) || trim($apiKey) === '') {
        throw new RuntimeException('BREVO_API_KEY is not configured.');
    }
    if (!is_string($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('SMTP_FROM_EMAIL must be a verified Brevo sender address.');
    }
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Reset recipient email address is invalid.');
    }

    $fromName = (string)app_config('SMTP_FROM_NAME', 'DONMA Attendance Tracking System');
    $payload = json_encode([
        'sender' => ['name' => $fromName, 'email' => $fromEmail],
        'to' => [['email' => $recipient, 'name' => $name]],
        'subject' => $subject,
        'htmlContent' => $htmlContent,
        'textContent' => $textContent,
    ], JSON_THROW_ON_ERROR);

    $result = brevoHttpPost('https://api.brevo.com/v3/smtp/email', trim($apiKey), $payload);
    $response = $result['body'];
    $statusCode = $result['status'];

    if ($response === null || $statusCode < 200 || $statusCode >= 300) {
        $responseData = is_string($response) ? json_decode($response, true) : null;
        $providerMessage = is_array($responseData) && is_string($responseData['message'] ?? null)
            ? trim($responseData['message'])
            : '';
        $detail = $statusCode > 0
            ? "Brevo email API returned HTTP {$statusCode}."
            : 'Could not connect to the Brevo email API over HTTPS (port 443).';
        if ($result['error'] !== '') {
            $detail .= ' Transport error: ' . $result['error'];
        }
        if ($providerMessage !== '') {
            $detail .= ' ' . substr($providerMessage, 0, 300);
        }
        if ($statusCode === 401 || $statusCode === 403) {
            $detail .= ' Check that brevo_api_key in config/deployment.php is a valid v3 API key.';
        }
        if ($statusCode === 400) {
            $detail .= ' Check that smtp_from_email in config/deployment.php is a sender verified in Brevo.';
        }
        mail_log('Brevo send failed: ' . $detail);
        throw new RuntimeException(
            $detail
        );
    }

    $responseData = json_decode((string)$response, true);
    return is_array($responseData) && is_string($responseData['messageId'] ?? null)
        ? $responseData['messageId']
        : "HTTP {$statusCode}";
}
