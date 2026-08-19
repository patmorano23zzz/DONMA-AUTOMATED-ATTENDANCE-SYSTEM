<?php
/**
 * API: Kiosk polls for new messages (public, same-origin)
 * GET ?since=<id>  — returns messages with id > since
 */
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host   = $_SERVER['HTTP_HOST']   ?? '';
$isSameOrigin = ($origin === '' || parse_url($origin, PHP_URL_HOST) === $host);
if (!$isSameOrigin) { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }

$pdo   = getDB();
$since = (int)($_GET['since'] ?? 0);

$rows = $pdo->prepare(
    "SELECT m.id, m.message, m.type, m.created_at,
            u.name AS teacher_name
     FROM kiosk_messages m
     JOIN users u ON u.id = m.teacher_id
     WHERE m.id > ?
     ORDER BY m.id ASC LIMIT 10"
);
$rows->execute([$since]);
echo json_encode(['messages' => $rows->fetchAll()]);
