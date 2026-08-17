<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { http_response_code(403); die('Unauthorized'); }

require_once __DIR__ . '/../../config/db.php';
$pdo = getDB();

$dateFrom = $_GET['from']    ?? $_GET['date'] ?? date('Y-m-d');
$dateTo   = $_GET['to']      ?? $dateFrom;
$section  = $_GET['section'] ?? '';

$where  = ['DATE(a.time_in) BETWEEN :from AND :to'];
$params = [':from' => $dateFrom, ':to' => $dateTo];
if ($section !== '') { $where[] = 's.grade_section = :section'; $params[':section'] = $section; }

// Teacher: scope to their own enrolled students only
if ($_SESSION['role'] === 'teacher') {
    $where[] = 's.teacher_id = :tid';
    $params[':tid'] = (int)$_SESSION['user_id'];
}

// Optional: filter by teacher_id from URL (admin viewing a specific teacher's export)
$filterTeacher = (int)($_GET['teacher_id'] ?? 0);
if ($filterTeacher > 0 && $_SESSION['role'] === 'admin') {
    $where[] = 's.teacher_id = :ftid';
    $params[':ftid'] = $filterTeacher;
}

$stmt = $pdo->prepare(
    'SELECT s.lrn,
            s.last_name, s.first_name, s.middle_name,
            s.grade_section,
            DATE(a.time_in)  AS date,
            TIME(a.time_in)  AS time_in,
            TIME(a.time_out) AS time_out,
            a.status
     FROM attendance a
     JOIN students s ON s.id = a.student_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY s.grade_section, s.last_name, a.time_in'
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'attendance_' . $dateFrom . '_to_' . $dateTo . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['LRN', 'Last Name', 'First Name', 'Middle Name', 'Grade/Section', 'Date', 'Time In', 'Time Out', 'Status']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['lrn'], $r['last_name'], $r['first_name'], $r['middle_name'] ?? '',
        $r['grade_section'], $r['date'], $r['time_in'] ?? '', $r['time_out'] ?? '', $r['status']
    ]);
}
fclose($out);
