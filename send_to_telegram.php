<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$fullName = trim((string)($data['name'] ?? $data['fullName'] ?? ''));
$email = trim((string)($data['email'] ?? $data['contactEmail'] ?? ''));
$subject = trim((string)($data['subject'] ?? ''));
$message = trim((string)($data['message'] ?? ''));
$workUrl = trim((string)($data['work_url'] ?? $data['workUrl'] ?? $data['website'] ?? $data['url'] ?? ''));

if ($fullName === '' || $email === '' || $subject === '' || $message === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields.']);
    exit;
}

$dbOk = false;
$dbError = null;

try {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "asset";

    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new RuntimeException($conn->connect_error);
    }

    $sql = "INSERT INTO contacts (full_name, contact_email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }

    $stmt->bind_param("ssss", $fullName, $email, $subject, $message);
    $dbOk = $stmt->execute();

    if (!$dbOk) {
        $dbError = $stmt->error ?: 'Database insert failed.';
    }

    $stmt->close();
    $conn->close();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$token = trim((string) getenv('TELEGRAM_BOT_TOKEN'));
$chatId = trim((string) getenv('TELEGRAM_CHAT_ID'));

if ($token === '' || $chatId === '') {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Telegram token or chat ID is missing. Please set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID in environment.'
    ]);
    exit;
}

$safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeSubject = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeWorkUrl = htmlspecialchars($workUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$text = "<b>📩 សារថ្មី!</b>\n\n"
    . "<b>ឈ្មោះ:</b> {$safeName}\n"
    . "<b>អ៊ីមែល:</b> {$safeEmail}\n"
    . "<b>ប្រធានបទ:</b> {$safeSubject}\n"
    . "<b>សារ:</b> {$safeMessage}";

if ($safeWorkUrl !== '') {
    $text .= "\n<b>Work URL:</b> {$safeWorkUrl}";
}

$apiUrl = "https://api.telegram.org/bot{$token}/sendMessage";
$payload = [
    'chat_id' => $chatId,
    'text' => $text,
    'parse_mode' => 'HTML'
];

$response = null;
$curlError = '';
$httpCode = 0;

if (!function_exists('curl_init') && !filter_var((string)ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Server cannot make HTTPS requests (cURL disabled and allow_url_fopen is off).'
    ]);
    exit;
}

if (function_exists('curl_init')) {
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 10
        ]
    ]);

    $response = file_get_contents($apiUrl, false, $context);
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $httpCode = (int)$matches[1];
    }
}

if ($response === false || $httpCode >= 400 || $httpCode === 0) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'ការផ្ញើសារបរាជ័យ។ សូមសាកល្បងម្ដងទៀត។',
        'details' => $curlError ?: $response
    ]);
    exit;
}

$tg = json_decode((string)$response, true);
if (!is_array($tg) || empty($tg['ok'])) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'ការផ្ញើសារបរាជ័យ។ សូមសាកល្បងម្ដងទៀត។',
        'details' => $tg['description'] ?? $response
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'db_ok' => $dbOk,
    'db_error' => $dbError
]);
