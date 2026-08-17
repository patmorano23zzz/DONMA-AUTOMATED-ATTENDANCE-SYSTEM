<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../authentication/login.php'); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
$pdo      = getDB();
$u        = APP_URL;
$userRole = $_SESSION['role'];
$userId   = (int)$_SESSION['user_id'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: list.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) { header('Location: list.php'); exit; }

// Teacher can only view their own enrolled students
if ($userRole === 'teacher' && (int)$student['teacher_id'] !== $userId) {
    header('Location: list.php'); exit;
}

// Handle inline edit POST
$editError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
    $fn  = trim($_POST['first_name']    ?? '');
    $mn  = trim($_POST['middle_name']   ?? '');
    $ln  = trim($_POST['last_name']     ?? '');
    $gs  = trim($_POST['grade_section'] ?? '');
    $bd  = $_POST['birthdate']           ?? '';
    $gen = $_POST['gender']              ?? '';
    $gn  = trim($_POST['guardian_name']  ?? '');
    $gp  = trim($_POST['guardian_phone'] ?? '');

    if (!$fn || !$ln || !$gs) {
        $editError = 'First name, last name and grade/section are required.';
    } else {
        $upd = $pdo->prepare(
            'UPDATE students SET first_name=?,middle_name=?,last_name=?,grade_section=?,
             birthdate=?,gender=?,guardian_name=?,guardian_phone=? WHERE id=?'
        );
        $upd->execute([$fn, $mn ?: null, $ln, $gs, $bd ?: null, $gen ?: null, $gn ?: null, $gp ?: null, $id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Student details updated successfully.'];
        header('Location: view.php?id=' . $id); exit;
    }
    // Reload student if error
    $stmt->execute([$id]);
    $student = $stmt->fetch();
}

// Recent attendance
$attStmt = $pdo->prepare('SELECT * FROM attendance WHERE student_id = ? ORDER BY time_in DESC LIMIT 30');
$attStmt->execute([$id]);
$records = $attStmt->fetchAll();

// Stats
$statsStmt = $pdo->prepare(
    'SELECT SUM(status="present") AS present, SUM(status="late") AS late,
            SUM(status="absent") AS absent, COUNT(*) AS total
     FROM attendance WHERE student_id = ?'
);
$statsStmt->execute([$id]);
$stats = $statsStmt->fetch();

$qrValue   = 'DCMMES-' . $student['lrn'];
$fullName  = $student['last_name'] . ', ' . $student['first_name'];
$activePage = 'students';
$pageTitle  = $student['first_name'] . ' ' . $student['last_name'];
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <style>
    /* ── Edit modal form ── */
    .dm-form-group { margin-bottom: 1rem; }
    .dm-form-group label { display:block; font-size:.82rem; font-weight:600;
      color:var(--cui-body-color,#374151); margin-bottom:.35rem; }
    .dm-form-group input, .dm-form-group select {
      width:100%; padding:.55rem .85rem; border-radius:8px;
      border:1.5px solid var(--cui-border-color,#d1d5db);
      background:var(--cui-body-bg,#fff); color:var(--cui-body-color,#111);
      font-size:.875rem; outline:none; transition:border-color .2s;
    }
    .dm-form-group input:focus, .dm-form-group select:focus {
      border-color:#1a56db; box-shadow:0 0 0 3px rgba(26,86,219,.12);
    }
    .dm-form-row { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
    .dm-form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.75rem; }
    @media(max-width:500px){ .dm-form-row,.dm-form-row-3 { grid-template-columns:1fr; } }
    .dm-edit-modal .dm-modal { max-width:560px; }
    .dm-edit-body { padding:0 1.5rem 1rem; text-align:left; max-height:70vh; overflow-y:auto; }
    .dm-edit-title { font-size:1rem; font-weight:700; color:var(--cui-body-color,#111);
      padding:1.25rem 1.5rem .75rem; border-bottom:1px solid var(--cui-border-color,#e5e7eb);
      display:flex; align-items:center; gap:.6rem; }
    .dm-edit-footer { padding:1rem 1.5rem 1.25rem; display:flex; gap:.6rem;
      justify-content:flex-end; border-top:1px solid var(--cui-border-color,#e5e7eb); }
    .dm-btn-save { background:#1a56db; color:#fff; border:none; padding:.6rem 1.6rem;
      border-radius:50px; font-weight:600; font-size:.875rem; cursor:pointer; transition:background .2s; }
    .dm-btn-save:hover { background:#1044b8; }
    .dm-btn-cancel-edit { background:transparent; color:var(--cui-body-color,#374151);
      border:1.5px solid var(--cui-border-color,#d1d5db); padding:.6rem 1.3rem;
      border-radius:50px; font-size:.875rem; cursor:pointer; transition:background .2s; }
    .dm-btn-cancel-edit:hover { background:var(--cui-body-tertiary-bg,#f3f4f6); }
    .dm-edit-error { background:#fee2e2; border:1px solid #fca5a5; color:#dc2626;
      border-radius:8px; padding:.6rem .9rem; font-size:.82rem; margin-bottom:.75rem; }

    /* ── QR modal ── */
    .dm-qr-modal .dm-modal { max-width:300px; }
    .dm-qr-body { padding:1rem 1.5rem; text-align:center; }
    .dm-qr-school { font-size:.65rem; font-weight:700; color:var(--cui-body-color,#111);
      line-height:1.3; margin-bottom:.75rem; }
    .dm-qr-name { font-weight:700; font-size:.9rem; margin-top:.6rem; }
    .dm-qr-lrn  { font-family:monospace; font-size:.75rem; color:var(--cui-secondary-color,#6b7280); }
    .dm-qr-sec  { font-size:.7rem; color:var(--cui-secondary-color,#6b7280); margin-bottom:.5rem; }
    .dm-qr-footer {
      padding:.75rem 1.25rem 1rem;
      display:flex; gap:.5rem; justify-content:center; align-items:center;
      border-top:1px solid var(--cui-border-color,#e5e7eb);
      flex-wrap:nowrap;
    }
    .dm-qr-btn {
      display:inline-flex; align-items:center; gap:.35rem;
      padding:.42rem .95rem; border-radius:6px; font-size:.8rem; font-weight:600;
      cursor:pointer; border:1.5px solid transparent; white-space:nowrap;
      transition:opacity .15s, transform .15s; text-decoration:none; line-height:1.4;
    }
    .dm-qr-btn:hover { opacity:.88; transform:translateY(-1px); }
    .dm-qr-btn-print { background:#1a56db; color:#fff; border-color:#1a56db; }
    .dm-qr-btn-pdf   { background:#16a34a; color:#fff; border-color:#16a34a; }
    .dm-qr-btn-close { background:transparent; color:var(--cui-body-color,#374151);
      border-color:var(--cui-border-color,#d1d5db); }
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
            <li class="breadcrumb-item"><a href="<?= $u ?>/students/list.php">Students</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($fullName) ?></li>
          </ol>
        </nav>

        <div class="row g-4">
          <!-- Profile card -->
          <div class="col-lg-4">
            <div class="card">
              <div class="card-body text-center">
                <?php
                $faceDir = __DIR__ . '/../assets/faces/' . $id;
                $faceImg = null;
                if (is_dir($faceDir)) {
                    $imgs = glob($faceDir . '/reg_0.jpg');
                    if ($imgs) $faceImg = 'assets/faces/' . $id . '/reg_0.jpg';
                }
                ?>
                <?php if ($faceImg): ?>
                <img src="<?= $u ?>/<?= htmlspecialchars($faceImg) ?>" width="100" height="100"
                  class="rounded-circle object-fit-cover border mb-3" alt="Student photo">
                <?php else: ?>
                <div class="rounded-circle bg-body-tertiary d-inline-flex align-items-center
                  justify-content-center mb-3" style="width:80px;height:80px;font-size:2rem;">
                  🎓
                </div>
                <?php endif; ?>

                <h5 class="fw-bold mb-1"><?= htmlspecialchars($fullName) ?></h5>
                <p class="text-body-secondary small mb-1"><?= htmlspecialchars($student['grade_section']) ?></p>
                <span class="badge bg-<?= ($student['enrollment_status'] ?? 'approved') === 'approved' ? 'success' : 'warning' ?> mb-3">
                  <?= htmlspecialchars(ucfirst($student['enrollment_status'] ?? 'approved')) ?>
                </span>

                <table class="table table-sm text-start mb-3">
                  <tr><th class="text-body-secondary">LRN</th>
                    <td class="font-monospace"><?= htmlspecialchars($student['lrn']) ?></td></tr>
                  <tr><th class="text-body-secondary">Gender</th>
                    <td><?= htmlspecialchars($student['gender'] ?? '—') ?></td></tr>
                  <tr><th class="text-body-secondary">Birthdate</th>
                    <td><?= $student['birthdate'] ? date('M j, Y', strtotime($student['birthdate'])) : '—' ?></td></tr>
                  <tr><th class="text-body-secondary">Guardian</th>
                    <td><?= htmlspecialchars($student['guardian_name'] ?? '—') ?></td></tr>
                  <tr><th class="text-body-secondary">Phone</th>
                    <td><?= htmlspecialchars($student['guardian_phone'] ?? '—') ?></td></tr>
                </table>

                <!-- Action buttons -->
                <div class="d-flex gap-2 justify-content-center">
                  <button class="btn btn-sm btn-outline-warning" id="btn-edit-student">
                    ✏️ Edit
                  </button>
                  <button class="btn btn-sm btn-outline-primary" id="btn-view-qr">
                    📱 View QR
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats + Attendance -->
          <div class="col-lg-8">
            <div class="row g-3 mb-4">
              <?php foreach ([
                ['Present', $stats['present'], 'success'],
                ['Late',    $stats['late'],    'warning'],
                ['Absent',  $stats['absent'],  'danger'],
                ['Total',   $stats['total'],   'primary'],
              ] as [$label, $val, $color]): ?>
              <div class="col-6 col-sm-3">
                <div class="card text-center border-<?= $color ?>">
                  <div class="card-body py-2">
                    <div class="fs-3 fw-bold text-<?= $color ?>"><?= (int)$val ?></div>
                    <div class="small text-body-secondary"><?= $label ?></div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="card">
              <div class="card-header fw-semibold">Recent Attendance (Last 30)</div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Date</th>
                        <th>Time In</th>
                        <th class="d-none d-sm-table-cell">Time Out</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($records)): ?>
                      <tr><td colspan="4" class="text-center text-body-secondary py-3">No attendance records yet.</td></tr>
                      <?php else: foreach ($records as $r): ?>
                      <tr>
                        <td><?= date('M j, Y', strtotime($r['time_in'])) ?></td>
                        <td><?= date('h:i A', strtotime($r['time_in'])) ?></td>
                        <td class="d-none d-sm-table-cell"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '—' ?></td>
                        <td>
                          <?php $b = match($r['status']) {
                            'present'=>'success','late'=>'warning','absent'=>'danger',default=>'secondary'
                          }; ?>
                          <span class="badge bg-<?= $b ?> text-capitalize"><?= $r['status'] ?></span>
                        </td>
                      </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
    <footer class="footer px-4 py-3 border-top">
      <div class="text-body-secondary small">
        &copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — ATS
      </div>
    </footer>
  </div>

  <!-- ══ QR MODAL ═══════════════════════════════════════════════════════════ -->
  <div class="dm-overlay dm-qr-modal" id="qr-modal" style="display:none;">
    <div class="dm-modal">
      <div class="dm-edit-title">
        📱 Student QR Code
        <button onclick="closeModal('qr-modal')"
          style="margin-left:auto;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#9ca3af;line-height:1;">✕</button>
      </div>
      <div class="dm-qr-body">
        <div class="dm-qr-school">
          Don Marcelo C. Marty Elementary School<br>
          <span style="font-weight:400;">Attendance Tracking System</span>
        </div>
        <div id="modal-qr-canvas" style="display:flex;justify-content:center;margin:.5rem 0;"></div>
        <div class="dm-qr-name"><?= htmlspecialchars($fullName) ?></div>
        <div class="dm-qr-lrn">LRN: <?= htmlspecialchars($student['lrn']) ?></div>
        <div class="dm-qr-sec"><?= htmlspecialchars($student['grade_section']) ?></div>
        <div class="font-monospace" style="font-size:.6rem;color:#aaa;margin-top:.3rem;">
          <?= htmlspecialchars($qrValue) ?>
        </div>
      </div>
      <div class="dm-qr-footer">
        <button class="dm-qr-btn dm-qr-btn-print" id="btn-qr-print">🖨 Print</button>
        <a class="dm-qr-btn dm-qr-btn-pdf"
           href="<?= $u ?>/api/students/qr-pdf.php?lrn=<?= urlencode($student['lrn']) ?>"
           download>📄 Save as PDF</a>
        <button class="dm-qr-btn dm-qr-btn-close" onclick="closeModal('qr-modal')">✕ Close</button>
      </div>
      <div id="qr-pdf-status" style="text-align:center;font-size:.78rem;padding:.25rem 0 .75rem;min-height:1.4em;color:#16a34a;"></div>
    </div>
  </div>

  <!-- ══ EDIT MODAL ═════════════════════════════════════════════════════════ -->
  <div class="dm-overlay dm-edit-modal" id="edit-modal" style="display:none;">
    <div class="dm-modal">
      <div class="dm-edit-title">
        ✏️ Edit Student — <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
        <button onclick="closeModal('edit-modal')"
          style="margin-left:auto;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#9ca3af;line-height:1;">✕</button>
      </div>
      <form method="POST" id="edit-form">
        <input type="hidden" name="edit_student" value="1">
        <?= csrf_field() ?>
        <div class="dm-edit-body">
          <?php if ($editError): ?>
          <div class="dm-edit-error"><?= htmlspecialchars($editError) ?></div>
          <?php endif; ?>

          <!-- LRN (read-only) -->
          <div class="dm-form-group">
            <label>LRN</label>
            <input type="text" value="<?= htmlspecialchars($student['lrn']) ?>" readonly
              style="background:var(--cui-secondary-bg,#f3f4f6);cursor:not-allowed;">
          </div>

          <!-- Name row -->
          <div class="dm-form-row-3">
            <div class="dm-form-group">
              <label>Last Name *</label>
              <input type="text" name="last_name" required
                value="<?= htmlspecialchars($student['last_name']) ?>">
            </div>
            <div class="dm-form-group">
              <label>First Name *</label>
              <input type="text" name="first_name" required
                value="<?= htmlspecialchars($student['first_name']) ?>">
            </div>
            <div class="dm-form-group">
              <label>Middle Name</label>
              <input type="text" name="middle_name"
                value="<?= htmlspecialchars($student['middle_name'] ?? '') ?>">
            </div>
          </div>

          <!-- Grade / Gender / Birthdate -->
          <div class="dm-form-row">
            <div class="dm-form-group">
              <label>Grade / Section *</label>
              <input type="text" name="grade_section" list="gs-opts" required
                value="<?= htmlspecialchars($student['grade_section']) ?>">
              <datalist id="gs-opts">
                <?php foreach (['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g): ?>
                <option value="<?= $g ?>">
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="dm-form-group">
              <label>Gender</label>
              <select name="gender">
                <option value="">— Select —</option>
                <option value="M" <?= ($student['gender']??'')==='M' ? 'selected':'' ?>>Male</option>
                <option value="F" <?= ($student['gender']??'')==='F' ? 'selected':'' ?>>Female</option>
                <option value="Other" <?= ($student['gender']??'')==='Other' ? 'selected':'' ?>>Other</option>
              </select>
            </div>
          </div>

          <div class="dm-form-group">
            <label>Birthdate</label>
            <input type="date" name="birthdate"
              value="<?= htmlspecialchars($student['birthdate'] ?? '') ?>">
          </div>

          <!-- Guardian -->
          <div class="dm-form-row">
            <div class="dm-form-group">
              <label>Guardian Name</label>
              <input type="text" name="guardian_name"
                value="<?= htmlspecialchars($student['guardian_name'] ?? '') ?>">
            </div>
            <div class="dm-form-group">
              <label>Guardian Phone</label>
              <input type="tel" name="guardian_phone"
                value="<?= htmlspecialchars($student['guardian_phone'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="dm-edit-footer">
          <button type="button" class="dm-btn-cancel-edit" onclick="closeModal('edit-modal')">Cancel</button>
          <button type="submit" class="dm-btn-save">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?= $u ?>/js/qrcode.min.js"></script>
  <script>
  const APP_URL  = <?= json_encode($u) ?>;
  const QR_VALUE = <?= json_encode($qrValue) ?>;
  let qrGenerated = false;

  // ── Modal helpers ─────────────────────────────────────────────────────────
  function openModal(id) {
    const el = document.getElementById(id);
    el.style.display = 'flex';
    requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('dm-show')));
    document.body.style.overflow = 'hidden';
  }

  function closeModal(id) {
    const el = document.getElementById(id);
    el.classList.remove('dm-show');
    setTimeout(() => { el.style.display = 'none'; document.body.style.overflow = ''; }, 220);
  }

  // Close on overlay click
  document.querySelectorAll('.dm-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(overlay.id); });
  });

  // ESC key closes any open modal
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.dm-overlay.dm-show').forEach(o => closeModal(o.id));
    }
  });

  // ── QR Modal ──────────────────────────────────────────────────────────────
  document.getElementById('btn-view-qr').addEventListener('click', () => {
    openModal('qr-modal');
    if (!qrGenerated) {
      new QRCode(document.getElementById('modal-qr-canvas'), {
        text: QR_VALUE, width: 180, height: 180,
        colorDark: '#000', colorLight: '#fff',
        correctLevel: QRCode.CorrectLevel.H
      });
      qrGenerated = true;
    }
  });

  // ── Print from modal ─────────────────────────────────────────────────────
  document.getElementById('btn-qr-print').addEventListener('click', () => {
    // Open the dedicated QR print page — clean, self-contained, no URL bloat
    const win = window.open(
      APP_URL + '/api/students/qr.php?lrn=<?= urlencode($student['lrn']) ?>',
      '_blank',
      'width=460,height=600,menubar=no,toolbar=no,location=no'
    );
    if (win) win.focus();
  });

  // ── Edit Modal ────────────────────────────────────────────────────────────
  document.getElementById('btn-edit-student').addEventListener('click', () => openModal('edit-modal'));

  <?php if ($editError): ?>
  // Re-open edit modal if there was a validation error
  document.addEventListener('DOMContentLoaded', () => openModal('edit-modal'));
  <?php endif; ?>
  </script>
</body>
</html>
