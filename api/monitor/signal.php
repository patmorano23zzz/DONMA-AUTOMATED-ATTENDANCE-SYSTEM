<?php
/**
 * WebRTC Signaling API
 * GET  ?role=viewer              — teacher polls for offer + ICE from kiosk
 * POST {role,type,data}          — kiosk posts offer/ICE; teacher posts answer/ICE
 * Rows expire after 60s to keep the table tiny (safe for InfinityFree)
 */
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host   = $_SERVER['HTTP_HOST']   ?? '';
$sameOrigin = ($origin === '' || parse_url($origin, PHP_URL_HOST) === $host);
$hasSession = !empty($_SESSION['user_id']);

// Kiosk (no session, same origin) OR logged-in teacher
if (!$sameOrigin && !$hasSession) {
    http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit;
}

$pdo = getDB();

// ── Purge stale signals older than 90s ──────────────────────────────────────
$pdo->exec("DELETE FROM webrtc_signals WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 SECOND)");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Teacher viewer polls: get all pending signals for role=viewer
    $role = $_GET['role'] ?? 'viewer';
    $since = (int)($_GET['since'] ?? 0);
    $rows = $pdo->prepare(
        "SELECT id, type, data FROM webrtc_signals WHERE target_role=? AND id>? ORDER BY id ASC LIMIT 20"
    );
    $rows->execute([$role, $since]);
    echo json_encode(['signals' => $rows->fetchAll()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $role   = $body['role']   ?? '';   // 'kiosk' or 'viewer'
    $type   = $body['type']   ?? '';   // 'offer','answer','ice-kiosk','ice-viewer'
    $data   = $body['data']   ?? null;

    if (!$role || !$type || $data === null) {
        echo json_encode(['error'=>'Missing fields']); exit;
    }

    // target_role is the opposite side
    $target = ($role === 'kiosk') ? 'viewer' : 'kiosk';

    $pdo->prepare(
        "INSERT INTO webrtc_signals (role, target_role, type, data, created_at) VALUES (?,?,?,?,NOW())"
    )->execute([$role, $target, $type, json_encode($data)]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
