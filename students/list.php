<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../authentication/login.php'); exit; }

require_once __DIR__ . '/../config/db.php';
$pdo = getDB();
$u   = APP_URL;

$search   = trim($_GET['q']       ?? '');
$section  = $_GET['section']      ?? '';
$defaultSections = [
    'Grade 1 - Mabini','Grade 1 - Rizal','Grade 2 - Mabini','Grade 2 - Rizal',
    'Grade 3 - Mabini','Grade 3 - Rizal','Grade 4 - Mabini','Grade 4 - Rizal',
    'Grade 5 - Mabini','Grade 5 - Rizal','Grade 6 - Mabini','Grade 6 - Rizal',
];
$dbSections = $pdo->query('SELECT DISTINCT grade_section FROM students ORDER BY grade_section')->fetchAll(PDO::FETCH_COLUMN);
$sections   = array_unique(array_merge($defaultSections, $dbSections));
sort($sections);

$where  = ['1=1', "s.enrollment_status = 'approved'"];
$params = [];

// Teacher: only see their own enrolled students
$userRole = $_SESSION['role'];
$userId   = (int)$_SESSION['user_id'];
if ($userRole === 'teacher') {
    $where[] = 's.teacher_id = :tid';
    $params[':tid'] = $userId;
}

if ($search !== '') {
    $where[] = "(s.lrn LIKE :q OR s.first_name LIKE :q OR s.last_name LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}
if ($section !== '') {
    $where[] = 's.grade_section = :section';
    $params[':section'] = $section;
}

$stmt = $pdo->prepare('SELECT s.* FROM students s WHERE ' . implode(' AND ', $where) . ' ORDER BY s.last_name, s.first_name');
$stmt->execute($params);
$students = $stmt->fetchAll();

$activePage = 'students';
$pageTitle  = 'Student List';
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <script src="<?= $u ?>/js/qrcode.min.js"></script>
  <style>
    /* QR modal preview */
    .qr-preview-box { display:flex; flex-direction:column; align-items:center; gap:.5rem; }
    .qr-preview-box canvas, .qr-preview-box img { border:1px solid #dee2e6; border-radius:4px; }
    @media(max-width:575px){ .d-mobile-none{ display:none !important; } }

    /* Batch print styles */
    @media print {
      body * { visibility: hidden; }
      #print-area, #print-area * { visibility: visible; }
      #print-area { position: fixed; inset: 0; background: #fff; }
      .qr-print-card {
        display: inline-flex !important;
        flex-direction: column;
        align-items: center;
        border: 1px solid #000;
        border-radius: 6px;
        padding: .6rem .8rem;
        margin: .4rem;
        width: 160px;
        text-align: center;
        page-break-inside: avoid;
        font-family: Arial, sans-serif;
      }
      .qr-print-name { font-size: .6rem; font-weight: 700; margin-top:.3rem; word-break:break-word; }
      .qr-print-lrn  { font-size: .55rem; font-family: monospace; color:#444; }
      .qr-print-sec  { font-size: .5rem; color:#666; }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>
    <div class="body flex-grow-1">
      <div class="container-lg px-4">

        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $u ?>/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Students</li>
          </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h4 class="mb-0">Student List</h4>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" id="btn-print-all"
              title="Print QR codes for all listed students">
              🖨 Print All QR Codes
            </button>
            <a href="<?= $u ?>/students/register.php" class="btn btn-primary btn-sm">+ Register Student</a>
          </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
          <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
              <div class="col-sm-5">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="q" class="form-control form-control-sm"
                  placeholder="Name or LRN…" value="<?= htmlspecialchars($search) ?>">
              </div>
              <div class="col-sm-4">
                <label class="form-label small mb-1">Section</label>
                <input type="text" name="section" list="section-list"
                  class="form-control form-control-sm" autocomplete="off"
                  placeholder="All Sections"
                  value="<?= htmlspecialchars($section) ?>">
                <datalist id="section-list">
                  <option value="Grade 1"><option value="Grade 2">
                  <option value="Grade 3"><option value="Grade 4">
                  <option value="Grade 5"><option value="Grade 6">
                  <?php foreach ($dbSections as $sec): ?><option value="<?= htmlspecialchars($sec) ?>"><?php endforeach; ?>
                </datalist>
              </div>
              <div class="col-sm-2">
                <button class="btn btn-primary btn-sm w-100">Search</button>
              </div>
              <div class="col-sm-1">
                <a href="<?= $u ?>/students/list.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
              </div>
            </form>
          </div>
        </div>

        <!-- Table -->
        <div class="card">
          <div class="card-header fw-semibold">
            <?= count($students) ?> student<?= count($students) !== 1 ? 's' : '' ?> found
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th class="d-mobile-none">#</th>
                    <th class="d-mobile-none">LRN</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th class="d-mobile-none">Grade / Section</th>
                    <th class="d-mobile-none">QR Code</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($students)): ?>
                  <tr><td colspan="7" class="text-center text-body-secondary py-4">No students found.</td></tr>
                  <?php else: ?>
                  <?php foreach ($students as $i => $s): ?>
                  <tr>
                    <td class="text-body-secondary small d-mobile-none"><?= $i + 1 ?></td>
                    <td class="font-monospace small d-mobile-none"><?= htmlspecialchars($s['lrn']) ?></td>
                    <td><?= htmlspecialchars($s['last_name']) ?></td>
                    <td><?= htmlspecialchars($s['first_name']) ?></td>
                    <td class="d-mobile-none"><?= htmlspecialchars($s['grade_section']) ?></td>
                    <td class="d-mobile-none">
                      <div id="qr-mini-<?= $s['id'] ?>" class="d-inline-block"
                           style="width:48px;height:48px;cursor:pointer;"
                           title="Click to view full QR"
                           data-lrn="<?= htmlspecialchars($s['lrn']) ?>"
                           data-name="<?= htmlspecialchars($s['last_name'].', '.$s['first_name']) ?>"
                           data-section="<?= htmlspecialchars($s['grade_section']) ?>"
                           onclick="openQrModal(this)">
                      </div>
                    </td>
                    <td>
                      <a href="<?= $u ?>/students/view.php?id=<?= $s['id'] ?>"
                         class="btn btn-sm btn-outline-secondary py-0 px-2 small">View</a>
                      <a href="<?= $u ?>/api/students/qr.php?lrn=<?= urlencode($s['lrn']) ?>"
                         class="btn btn-sm btn-outline-primary py-0 px-2 small d-mobile-none" target="_blank">Print QR</a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>

    <footer class="footer px-4 py-3 border-top">
      <div class="text-body-secondary small">&copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — ATS</div>
    </footer>
  </div>

  <!-- ── QR Preview Modal ─────────────────────────────────────────────────── -->
  <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:320px">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-semibold" id="qrModalLabel">Student QR Code</h5>
          <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <div class="qr-preview-box">
            <div class="small fw-semibold text-body-secondary" style="font-size:.65rem;">
              Don Marcelo C. Marty Elementary School<br>
              <span style="font-weight:400;">Attendance Tracking System</span>
            </div>
            <div id="qr-modal-canvas"></div>
            <div class="fw-bold" id="qr-modal-name" style="font-size:.9rem;"></div>
            <div class="font-monospace text-body-secondary" id="qr-modal-lrn" style="font-size:.75rem;"></div>
            <div class="text-body-secondary" id="qr-modal-section" style="font-size:.7rem;"></div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
          <button class="btn btn-primary btn-sm" id="btn-modal-print"
            onclick="printSingleQR()">🖨 Print</button>
          <a class="btn btn-outline-secondary btn-sm" id="btn-modal-full" target="_blank">Open Full Page</a>
          <button type="button" class="btn btn-outline-secondary btn-sm"
            data-coreui-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden batch print area -->
  <div id="print-area" style="display:none;"></div>

  <!-- Embed student data for JS -->
  <script>
    const STUDENTS = <?= json_encode(array_map(fn($s) => [
      'id'      => $s['id'],
      'lrn'     => $s['lrn'],
      'name'    => $s['last_name'] . ', ' . $s['first_name'],
      'section' => $s['grade_section'],
      'qrval'   => 'DCMMES-' . $s['lrn'],
    ], $students), JSON_UNESCAPED_UNICODE) ?>;
    const APP_URL = <?= json_encode($u) ?>;
  </script>

  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
  <script>
  // ── Generate mini thumbnails in each table row ───────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    STUDENTS.forEach(s => {
      const el = document.getElementById('qr-mini-' + s.id);
      if (!el) return;
      new QRCode(el, {
        text: s.qrval,
        width: 48, height: 48,
        colorDark: '#000', colorLight: '#fff',
        correctLevel: QRCode.CorrectLevel.M
      });
    });
  });

  // ── QR Modal ─────────────────────────────────────────────────────────────
  let currentQrLrn = '';
  function openQrModal(el) {
    const lrn     = el.dataset.lrn;
    const name    = el.dataset.name;
    const section = el.dataset.section;
    const qrval   = 'DCMMES-' + lrn;
    currentQrLrn  = lrn;

    document.getElementById('qr-modal-name').textContent    = name;
    document.getElementById('qr-modal-lrn').textContent     = 'LRN: ' + lrn;
    document.getElementById('qr-modal-section').textContent = section;
    document.getElementById('btn-modal-full').href = APP_URL + '/api/students/qr.php?lrn=' + encodeURIComponent(lrn);

    // Clear old QR and generate new one
    const container = document.getElementById('qr-modal-canvas');
    container.innerHTML = '';
    new QRCode(container, {
      text: qrval,
      width: 200, height: 200,
      colorDark: '#000', colorLight: '#fff',
      correctLevel: QRCode.CorrectLevel.H
    });

    const modal = new coreui.Modal(document.getElementById('qrModal'));
    modal.show();
  }

  // ── Print single QR from modal ───────────────────────────────────────────
  function printSingleQR() {
    window.open(APP_URL + '/api/students/qr.php?lrn=' + encodeURIComponent(currentQrLrn), '_blank');
  }

  // ── Batch print all listed students ──────────────────────────────────────
  document.getElementById('btn-print-all')?.addEventListener('click', () => {
    if (STUDENTS.length === 0) {
    DonmaModal.alert({ title: 'No Students', message: 'No students found in the current list to print.', type: 'info' });
    return;
  }

    const area = document.getElementById('print-area');
    area.innerHTML = '';
    area.style.display = 'block';

    let rendered = 0;
    STUDENTS.forEach(s => {
      const card = document.createElement('div');
      card.className = 'qr-print-card';
      card.innerHTML = `
        <div style="font-size:.5rem;font-weight:700;text-align:center;line-height:1.2;margin-bottom:.2rem;">
          Don Marcelo C. Marty<br>Elementary School
        </div>
        <div id="batch-qr-${s.id}"></div>
        <div class="qr-print-name">${s.name}</div>
        <div class="qr-print-lrn">LRN: ${s.lrn}</div>
        <div class="qr-print-sec">${s.section}</div>
      `;
      area.appendChild(card);

      new QRCode(document.getElementById('batch-qr-' + s.id), {
        text: s.qrval,
        width: 120, height: 120,
        colorDark: '#000', colorLight: '#fff',
        correctLevel: QRCode.CorrectLevel.H
      });
      rendered++;
    });

    // Wait for QR canvases to render then print
    setTimeout(() => {
      window.print();
      setTimeout(() => { area.style.display = 'none'; area.innerHTML = ''; }, 500);
    }, 800);
  });
  </script>
</body>
</html>
