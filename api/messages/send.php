<?php
/**
 * API: Teacher sends a message to the kiosk display
 * POST { "message": "...", "type": "info|warning|success" }
 */
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}
require_once __DIR__ . '/../../config/db.php';
$pdo  = getDB();
$body = json_decode(file_get_contents('php://input'), true);
$msg  = trim($body['message'] ?? '');
$type = in_array($body['type'] ?? '', ['info','warning','success']) ? $body['type'] : 'info';

if ($msg === '' || strlen($msg) > 300) {
    echo json_encode(['success' => false, 'message' => 'Message must be 1–300 characters.']); exit;
}

$pdo->prepare(
    "INSERT INTO kiosk_messages (teacher_id, message, type, created_at) VALUES (?, ?, ?, NOW())"
)->execute([$_SESSION['user_id'], $msg, $type]);

echo json_encode(['success' => true]);
