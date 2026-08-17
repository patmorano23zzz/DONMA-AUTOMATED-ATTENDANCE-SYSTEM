<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Allow logged-in staff OR students coming from their own registration (no user session)
// Students access this right after registering — they have no staff session
$isStaff   = !empty($_SESSION['user_id']);
$fromReg   = !empty($_SESSION['reg_lrn']) || !empty($_GET['lrn']); // public QR view allowed
if (!$isStaff && !$fromReg) {
    header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/authentication/login.php?force=1');
    exit;
}
require_once __DIR__ . '/../../config/db.php';
$pdo = getDB();
$u   = APP_URL;

$lrn = preg_replace('/\D/', '', trim($_GET['lrn'] ?? ''));
if (strlen($lrn) < 10 || strlen($lrn) > 12) {
    http_response_code(400); die('Invalid LRN — must be 10 to 12 digits');
}

$stmt = $pdo->prepare('SELECT * FROM students WHERE lrn = ? LIMIT 1');
$stmt->execute([$lrn]);
$student = $stmt->fetch();
if (!$student) { http_response_code(404); die('Student not found'); }

$qrValue  = 'DCMMES-' . $lrn;
$fullName = $student['last_name'] . ', ' . $student['first_name'];
$section  = $student['grade_section'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QR — <?= htmlspecialchars($fullName) ?></title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{
      font-family:Arial,sans-serif;background:#f0f2f5;
      min-height:100vh;display:flex;flex-direction:column;
      align-items:center;justify-content:center;padding:2rem 1rem;gap:1.5rem;
    }
    .qr-card{
      background:#fff;border:2px solid #1a1a1a;border-radius:12px;
      padding:1.5rem 2rem 1.25rem;text-align:center;width:280px;
      box-shadow:0 4px 16px rgba(0,0,0,.12);
    }
    .school-logo{margin:0 auto .5rem;width:44px;height:44px}
    .school-name{font-size:.72rem;font-weight:700;line-height:1.3}
    .school-sub{font-size:.6rem;color:#555;margin-bottom:.75rem}
    #qr-canvas{margin:.5rem auto;display:flex;justify-content:center;min-height:195px}
    #qr-canvas canvas,#qr-canvas img{display:block}
    .student-name{font-size:.95rem;font-weight:700;margin-top:.6rem}
    .student-lrn{font-size:.72rem;font-family:monospace;color:#444;margin-top:.15rem}
    .student-sec{font-size:.65rem;color:#666;margin-top:.1rem}
    .qr-value{font-size:.55rem;color:#aaa;margin-top:.4rem;font-family:monospace;word-break:break-all}
    .actions{display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center}
    .btn{
      padding:.55rem 1.3rem;border-radius:8px;font-size:.875rem;cursor:pointer;
      border:1.5px solid transparent;display:inline-flex;align-items:center;gap:.4rem;
      font-weight:600;transition:opacity .15s,transform .15s;
    }
    .btn:hover{opacity:.88;transform:translateY(-1px)}
    .btn-print{background:#0d6efd;color:#fff;border-color:#0d6efd}
    .btn-pdf  {background:#16a34a;color:#fff;border-color:#16a34a}
    .btn-back {background:#fff;color:#333;border-color:#ccc}
    .btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
    #pdf-status{font-size:.78rem;color:#16a34a;min-height:1.2em;text-align:center}
    @media print{
      body{background:#fff;padding:0;justify-content:flex-start;padding-top:1rem}
      .actions,#pdf-status{display:none}
      .qr-card{box-shadow:none;border-color:#000}
    }
  </style>
</head>
<body>

  <div class="qr-card" id="qr-card">
    <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" class="school-logo" style="border-radius:50%;object-fit:cover;">
    <div class="school-name">Don Marcelo C. Marty Elementary School</div>
    <div class="school-sub">Automated Attendance Tracking System</div>
    <div id="qr-canvas"></div>
    <div class="student-name"><?= htmlspecialchars($fullName) ?></div>
    <div class="student-lrn">LRN: <?= htmlspecialchars($lrn) ?></div>
    <div class="student-sec"><?= htmlspecialchars($section) ?></div>
    <div class="qr-value"><?= htmlspecialchars($qrValue) ?></div>
  </div>

  <div id="pdf-status"></div>

  <div class="actions">
    <button class="btn btn-print" id="btn-print">🖨 Print</button>
    <button class="btn btn-pdf"   id="btn-pdf">📄 Save as PDF</button>
    <a class="btn btn-back" href="#" onclick="window.close();return false;">✕ Close</a>
  </div>

  <!-- Local QR library — no CDN dependency -->
  <script src="<?= $u ?>/js/qrcode.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script>
    const QR_VALUE  = <?= json_encode($qrValue) ?>;
    const FILENAME  = 'QR_<?= $lrn ?>_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $fullName) ?>.pdf';

    // ── Generate QR ────────────────────────────────────────────────────────
    new QRCode(document.getElementById('qr-canvas'), {
      text:         QR_VALUE,
      width:        190,
      height:       190,
      colorDark:    '#000000',
      colorLight:   '#ffffff',
      correctLevel: QRCode.CorrectLevel.H
    });

    // ── Auto-trigger PDF if ?action=pdf ────────────────────────────────────
    const AUTO_ACTION = <?= json_encode($_GET['action'] ?? '') ?>;
    if (AUTO_ACTION === 'pdf') {
      // Wait for QR to render then click the PDF button
      const tryClick = (attempts) => {
        const img = document.querySelector('#qr-canvas img');
        const cvs = document.querySelector('#qr-canvas canvas');
        if ((img && img.complete && img.naturalWidth > 0) || cvs) {
          document.getElementById('btn-pdf').click();
        } else if (attempts < 30) {
          setTimeout(() => tryClick(attempts + 1), 100);
        }
      };
      setTimeout(() => tryClick(0), 200);
    }

    // ── Print ──────────────────────────────────────────────────────────────
    document.getElementById('btn-print').addEventListener('click', () => {
      window.print();
    });

    // ── Save as PDF (actual file download) ─────────────────────────────────
    document.getElementById('btn-pdf').addEventListener('click', async () => {
      const btn    = document.getElementById('btn-pdf');
      const status = document.getElementById('pdf-status');
      btn.disabled = true;
      btn.textContent = '⏳ Generating…';
      status.textContent = '';

      try {
        // Wait for QR image to be fully loaded
        const qrImg = document.querySelector('#qr-canvas img');
        if (qrImg && !qrImg.complete) {
          await new Promise(res => { qrImg.onload = res; qrImg.onerror = res; });
        }

        // Capture the card as canvas
        const card    = document.getElementById('qr-card');
        const canvas  = await html2canvas(card, {
          scale:            3,
          backgroundColor:  '#ffffff',
          useCORS:          true,
          allowTaint:       true,
          logging:          false,
        });

        const imgData = canvas.toDataURL('image/png');

        // Build PDF — A6 card size (105 × 148 mm)
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a6' });

        const pageW = pdf.internal.pageSize.getWidth();
        const pageH = pdf.internal.pageSize.getHeight();
        const ratio = canvas.width / canvas.height;
        let imgW = pageW - 10;       // 5mm margin each side
        let imgH = imgW / ratio;
        if (imgH > pageH - 10) { imgH = pageH - 10; imgW = imgH * ratio; }
        const x = (pageW - imgW) / 2;
        const y = (pageH - imgH) / 2;

        pdf.addImage(imgData, 'PNG', x, y, imgW, imgH);
        pdf.save(FILENAME);

        status.textContent = '✅ PDF saved!';
        setTimeout(() => { status.textContent = ''; }, 3000);

      } catch (err) {
        console.error(err);
        status.textContent = '❌ Failed. Try the Print button instead.';
        status.style.color = '#dc2626';
      }

      btn.disabled    = false;
      btn.textContent = '📄 Save as PDF';
    });
  </script>
</body>
</html>