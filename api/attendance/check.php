<?php
/**
 * API: Check if a student QR is valid and attendance status for today
 * POST body: { "lrn": "123456789012" }
 * Only accessible from the same server (kiosk).
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

// Require valid session OR same-server IP — kiosk pages are logged-out public pages
// so we allow same-origin requests (kiosk.php calls these from the browser)
if (session_status() === PHP_SESSION_NONE) session_start();
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
$serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
$hasSession = !empty($_SESSION['user_id']);
$isSameHost = in_array($remoteAddr, ['127.0.0.1', '::1', $serverAddr]);
$origin     = $_SERVER['HTTP_ORIGIN'] ?? '';
$host       = $_SERVER['HTTP_HOST']   ?? '';
$isSameOrigin = $isSameHost || ($origin !== '' && parse_url($origin, PHP_URL_HOST) === $host);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Must be same origin (browser kiosk) or authenticated session
if (!$isSameOrigin && !$hasSession) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$lrn  = trim($body['lrn'] ?? '');

if ($lrn === '') {
    echo json_encode(['valid' => false, 'message' => 'LRN is required.']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare(
    "SELECT id, CONCAT(first_name,' ',last_name) AS name, grade_section, enrollment_status
     FROM students WHERE lrn = ? LIMIT 1"
);
$stmt->execute([$lrn]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['valid' => false, 'message' => 'Student not found. LRN not registered.']);
    exit;
}

if (($student['enrollment_status'] ?? 'approved') === 'pending') {
    echo json_encode(['valid' => false, 'message' => 'Enrollment is pending admin approval. Cannot record attendance yet.']);
    exit;
}

// Check today's record
$today = date('Y-m-d');
$aStmt = $pdo->prepare(
    'SELECT id, time_in, time_out, status FROM attendance WHERE student_id = ? AND DATE(time_in) = ? LIMIT 1'
);
$aStmt->execute([$student['id'], $today]);
$att = $aStmt->fetch();

$fullyRecorded = $att && $att['time_out'] !== null;

echo json_encode([
    'valid'          => true,
    'student_id'     => $student['id'],
    'name'           => $student['name'],
    'grade_section'  => $student['grade_section'],
    'fully_recorded' => $fullyRecorded,
    'time_in'        => $att['time_in']  ?? null,
    'time_out'       => $att['time_out'] ?? null,
    'status'         => $att['status']   ?? null,
]);
