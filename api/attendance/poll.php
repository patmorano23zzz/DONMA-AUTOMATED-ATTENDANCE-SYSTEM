<?php
/**
 * API: Lightweight polling endpoint for dashboard live stats
 * GET /api/attendance/poll.php
 * Returns today's attendance counts — designed for 10-second polling on InfinityFree.
 * Cached server-side for 8 seconds to reduce DB hits.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
// Tell browser not to cache — we handle it server-side
header('Cache-Control: no-store');

require_once __DIR__ . '/../../config/db.php';

$today    = date('Y-m-d');
$userRole = $_SESSION['role'] ?? 'guest';
$userId   = (int)($_SESSION['user_id'] ?? 0);

// ── Server-side cache via file (8 sec TTL — safe for InfinityFree) ────────
$cacheDir  = __DIR__ . '/../../assets/cache';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

$cacheKey  = 'poll_' . $userRole . '_' . $userId . '_' . $today;
$cacheFile = $cacheDir . '/' . md5($cacheKey) . '.json';
$cacheTTL  = 8; // seconds

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    echo file_get_contents($cacheFile);
    exit;
}

// ── Fresh DB query ─────────────────────────────────────────────────────────
try {
    $pdo = getDB();

    if ($userRole === 'teacher') {
        // Total approved students for this teacher
        $s = $pdo->prepare("SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='approved'");
        $s->execute([$userId]);
        $total = (int)$s->fetchColumn();

        // Present today (students under this teacher)
        $s2 = $pdo->prepare(
            "SELECT COUNT(DISTINCT a.student_id) FROM attendance a
             JOIN students s ON s.id = a.student_id
             WHERE s.teacher_id=? AND s.enrollment_status='approved' AND DATE(a.time_in)=?"
        );
        $s2->execute([$userId, $today]);
        $present = (int)$s2->fetchColumn();

        // Late today
        $s3 = $pdo->prepare(
            "SELECT COUNT(*) FROM attendance a
             JOIN students s ON s.id = a.student_id
             WHERE s.teacher_id=? AND s.enrollment_status='approved'
               AND DATE(a.time_in)=? AND a.status='late'"
        );
        $s3->execute([$userId, $today]);
        $late = (int)$s3->fetchColumn();

        // Pending enrollments for this teacher
        $s4 = $pdo->prepare("SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='pending'");
        $s4->execute([$userId]);
        $pending = (int)$s4->fetchColumn();

    } else {
        // Admin: school-wide
        $total   = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE enrollment_status='approved'")->fetchColumn();

        $s2 = $pdo->prepare('SELECT COUNT(DISTINCT student_id) FROM attendance WHERE DATE(time_in)=?');
        $s2->execute([$today]);
        $present = (int)$s2->fetchColumn();

        $s3 = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE DATE(time_in)=? AND status='late'");
        $s3->execute([$today]);
        $late = (int)$s3->fetchColumn();

        $pending = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE enrollment_status='pending'")->fetchColumn();
    }

    $absent = max(0, $total - $present);

    $data = [
        'total'    => $total,
        'present'  => $present,
        'late'     => $late,
        'absent'   => $absent,
        'pending'  => $pending,
        'time'     => date('H:i:s'),
        'cached'   => false,
    ];

    $json = json_encode($data);
    file_put_contents($cacheFile, $json);
    echo $json;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
