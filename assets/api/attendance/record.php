<?php
/**
 * API: Record attendance (time-in or time-out)
 * POST body: { "lrn": "...", "face_image": "..." | null }
 * Only accessible from the same server (kiosk).
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

// Require valid session OR same-origin browser request (kiosk)
if (session_status() === PHP_SESSION_NONE) session_start();
$remoteAddr   = $_SERVER['REMOTE_ADDR'] ?? '';
$serverAddr   = $_SERVER['SERVER_ADDR'] ?? '';
$hasSession   = !empty($_SESSION['user_id']);
$isSameHost   = in_array($remoteAddr, ['127.0.0.1', '::1', $serverAddr]);
$origin       = $_SERVER['HTTP_ORIGIN'] ?? '';
$host         = $_SERVER['HTTP_HOST']   ?? '';
$isSameOrigin = $isSameHost || ($origin !== '' && parse_url($origin, PHP_URL_HOST) === $host);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

if (!$isSameOrigin && !$hasSession) {
    http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit;
}

$body      = json_decode(file_get_contents('php://input'), true);
$lrn       = trim($body['lrn']        ?? '');
$faceImage = $body['face_image']      ?? null;   // base64 data-URL or null

if ($lrn === '') {
    echo json_encode(['success' => false, 'message' => 'LRN is required.']); exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare(
    "SELECT id, CONCAT(first_name,' ',last_name) AS name, enrollment_status
     FROM students WHERE lrn = ? LIMIT 1"
);
$stmt->execute([$lrn]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found. LRN not registered.']); exit;
}

if (($student['enrollment_status'] ?? 'approved') === 'pending') {
    echo json_encode(['success' => false, 'message' => 'Enrollment pending admin approval. Cannot record attendance yet.']); exit;
}

$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

// School cut-off time for "late" — default 08:00
$cutOff   = '08:00:00';
$settings = $pdo->query("SELECT cut_off_time FROM settings LIMIT 1")->fetch();
if ($settings) $cutOff = $settings['cut_off_time'];

$isLate = (date('H:i:s') > $cutOff);
$status = $isLate ? 'late' : 'present';

// Existing record today?
$aStmt = $pdo->prepare(
    'SELECT id, time_in, time_out FROM attendance WHERE student_id = ? AND DATE(time_in) = ? LIMIT 1'
);
$aStmt->execute([$student['id'], $today]);
$att = $aStmt->fetch();

// Save face image if provided (max ~2MB decoded, JPEG only)
$facePath = null;
if ($faceImage) {
    // Strip data URI prefix and decode
    $raw = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $faceImage));
    // Validate: must be a real JPEG (starts with FF D8 FF)
    $isJpeg = $raw && strlen($raw) >= 3
              && ord($raw[0]) === 0xFF
              && ord($raw[1]) === 0xD8
              && ord($raw[2]) === 0xFF;
    if ($isJpeg && strlen($raw) <= 2 * 1024 * 1024) {
        $dir = __DIR__ . '/../../assets/faces/' . $student['id'];
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = $today . '_' . time() . '.jpg';
        file_put_contents($dir . '/' . $filename, $raw);
        $facePath = 'assets/faces/' . $student['id'] . '/' . $filename;
    }
}

if (!$att) {
    // TIME IN
    $ins = $pdo->prepare(
        'INSERT INTO attendance (student_id, time_in, status, face_image, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $ins->execute([$student['id'], $now, $status, $facePath]);

    echo json_encode([
        'success'  => true,
        'action'   => 'timein',
        'name'     => $student['name'],
        'time_in'  => date('h:i:s A', strtotime($now)),
        'status'   => $status,
    ]);

} elseif ($att['time_out'] === null) {
    // TIME OUT
    $upd = $pdo->prepare(
        'UPDATE attendance SET time_out = ?, face_image_out = ? WHERE id = ?'
    );
    $upd->execute([$now, $facePath, $att['id']]);

    echo json_encode([
        'success'   => true,
        'action'    => 'timeout',
        'name'      => $student['name'],
        'time_out'  => date('h:i:s A', strtotime($now)),
        'status'    => 'present',
    ]);

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Attendance already fully recorded for today.',
    ]);
}
