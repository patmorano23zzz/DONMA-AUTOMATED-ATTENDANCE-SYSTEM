<?php
/**
 * Server-side PDF generator — FPDF + phpqrcode (fully offline, no CDN).
 * GET /api/students/qr-pdf.php?lrn=1014580001
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { http_response_code(403); die('Unauthorized'); }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../vendors/fpdf/fpdf.php';
require_once __DIR__ . '/../../vendors/phpqrcode/qrlib.php';

$pdo = getDB();
$lrn = preg_replace('/\D/', '', trim($_GET['lrn'] ?? ''));
if (strlen($lrn) < 10 || strlen($lrn) > 12) { http_response_code(400); die('Invalid LRN'); }

$stmt = $pdo->prepare('SELECT * FROM students WHERE lrn = ? LIMIT 1');
$stmt->execute([$lrn]);
$student = $stmt->fetch();
if (!$student) { http_response_code(404); die('Student not found'); }

$qrValue  = 'DCMMES-' . $lrn;
$fullName = $student['last_name'] . ', ' . $student['first_name'];
$section  = $student['grade_section'];

// ── Generate QR PNG locally using phpqrcode ───────────────────────────────
$tmpQr = sys_get_temp_dir() . '/donma_qr_' . $lrn . '_' . uniqid() . '.png';
QRcode::png($qrValue, $tmpQr, QR_ECLEVEL_H, 6, 2);

// ── Build PDF (80×105 mm — ID card size) ─────────────────────────────────
$pdf = new FPDF('P', 'mm', [80, 105]);
$pdf->AddPage();
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

// White background
$pdf->SetFillColor(255, 255, 255);
$pdf->Rect(0, 0, 80, 105, 'F');

// Border
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.4);
$pdf->RoundedRect = false; // not available in base FPDF — use Rect
$pdf->Rect(2, 2, 76, 101);

// ── School header ──────────────────────────────────────────────────────────
$pdf->SetFont('Arial', 'B', 7.5);
$pdf->SetTextColor(30, 41, 59);
$pdf->SetXY(4, 5);
$pdf->MultiCell(72, 3.8, 'Don Marcelo C. Marty Elementary School', 0, 'C');

$pdf->SetFont('Arial', '', 6);
$pdf->SetTextColor(100, 116, 139);
$pdf->SetXY(4, 12);
$pdf->Cell(72, 3.5, 'Automated Attendance Tracking System', 0, 1, 'C');

// Divider
$pdf->SetDrawColor(226, 232, 240);
$pdf->Line(4, 16.5, 76, 16.5);

// ── QR Code image ─────────────────────────────────────────────────────────
$qrSize = 46; // mm
$qrX    = (80 - $qrSize) / 2;
$pdf->Image($tmpQr, $qrX, 19, $qrSize, $qrSize, 'PNG');

// Cleanup temp file
@unlink($tmpQr);

// ── Student info ──────────────────────────────────────────────────────────
$yStart = 19 + $qrSize + 3; // below QR + small gap

$pdf->SetFont('Arial', 'B', 9.5);
$pdf->SetTextColor(15, 23, 42);
$pdf->SetXY(4, $yStart);
$pdf->Cell(72, 5, $fullName, 0, 1, 'C');

$pdf->SetFont('Courier', '', 7.5);
$pdf->SetTextColor(71, 85, 105);
$pdf->SetXY(4, $yStart + 5);
$pdf->Cell(72, 4, 'LRN: ' . $lrn, 0, 1, 'C');

$pdf->SetFont('Arial', '', 7);
$pdf->SetTextColor(100, 116, 139);
$pdf->SetXY(4, $yStart + 9);
$pdf->Cell(72, 4, $section, 0, 1, 'C');

// QR value small
$pdf->SetFont('Courier', '', 5.5);
$pdf->SetTextColor(180, 180, 180);
$pdf->SetXY(4, $yStart + 13);
$pdf->Cell(72, 3.5, $qrValue, 0, 1, 'C');

// ── Output ────────────────────────────────────────────────────────────────
$filename = 'QR_' . $lrn . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $fullName) . '.pdf';
$pdf->Output('D', $filename);
