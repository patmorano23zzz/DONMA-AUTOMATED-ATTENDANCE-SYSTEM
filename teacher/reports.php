<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../authentication/login.php'); exit;
}
require_once __DIR__ . '/../config/db.php';
$pdo       = getDB();
$u         = APP_URL;
$teacherId = (int)$_SESSION['user_id'];

$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-d');

$summary = $pdo->prepare(
    "SELECT s.id AS student_id, s.lrn,
            CONCAT(s.last_name,', ',s.first_name) AS student_name,
            s.grade_section,
            SUM(a.status='present') AS cnt_present,
            SUM(a.status='late')    AS cnt_late,
            SUM(a.status='absent')  AS cnt_absent,
            COUNT(a.id)             AS cnt_total
     FROM students s
     LEFT JOIN attendance a ON a.student_id = s.id
       AND DATE(a.time_in) BETWEEN ? AND ?
     WHERE s.teacher_id = ? AND s.enrollment_status = 'approved'
     GROUP BY s.id ORDER BY s.grade_section, s.last_name"
);
$summary->execute([$dateFrom, $dateTo, $teacherId]);
$rows = $summary->fetchAll();

// Today's attendance with face images for this teacher's students
$today = date('Y-m-d');
$todayLogs = $pdo->prepare(
    "SELECT a.id, CONCAT(s.last_name,', ',s.first_name) AS student_name,
            s.grade_section, a.time_in, a.time_out, a.status,
            a.face_image, a.face_image_out
     FROM attendance a
     JOIN students s ON s.id = a.student_id
     WHERE s.teacher_id = ? AND s.enrollment_status = 'approved' AND DATE(a.time_in) = ?
     ORDER BY a.time_in DESC"
);
$todayLogs->execute([$teacherId, $today]);
$todayRows = $todayLogs->fetchAll();

$myTotal   = (int)$pdo->prepare("SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='approved'")->execute([$teacherId]) ? $pdo->prepare("SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='approved'")->execute([$teacherId]) : 0;
$stTotal   = $pdo->prepare("SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='approved'");
$stTotal->execute([$teacherId]); $myTotal = (int)$stTotal->fetchColumn();

$stPend    = $pdo->prepare("SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='pending'");
$stPend->execute([$teacherId]); $myPending = (int)$stPend->fetchColumn();

// Daily chart
$chartLabels = []; $chartPresent = []; $chartLate = [];
$period = new DatePeriod(new DateTime($dateFrom), new DateInterval('P1D'), (new DateTime($dateTo))->modify('+1 day'));
foreach ($period as $day) {
    $d = $day->format('Y-m-d');
    $r = $pdo->prepare("SELECT SUM(a.status='present') AS p, SUM(a.status='late') AS l FROM attendance a JOIN students s ON s.id=a.student_id WHERE s.teacher_id=? AND DATE(a.time_in)=?");
    $r->execute([$teacherId, $d]); $row = $r->fetch();
    $chartLabels[]  = $day->format('M j');
    $chartPresent[] = (int)($row['p'] ?? 0);
    $chartLate[]    = (int)($row['l'] ?? 0);
}

$activePage = 'teacher-reports';
$pageTitle  = 'My Class Reports';
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <link href="<?= $u ?>/vendors/@coreui/chartjs/css/coreui-chartjs.css" rel="stylesheet">
  <style>
  .face-thumb{width:44px;height:44px;border-radius:8px;object-fit:cover;cursor:pointer;border:2px solid #dee2e6;transition:transform .2s}
  .face-thumb:hover{transform:scale(1.1);border-color:#1a6b3a}
  .face-placeholder{width:44px;height:44px;border-radius:8px;background:#f0f0f0;display:inline-flex;align-items:center;justify-content:center;font-size:1.2rem;color:#aaa}
  #msg-toast{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;min-width:260px;display:none}
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
            <li class="breadcrumb-item active">My Class Reports</li>
          </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <div>
            <h4 class="mb-0">My Class Attendance Report</h4>
            <p class="text-body-secondary small mb-0 mt-1">
              <strong><?= $myTotal ?></strong> enrolled
              <?php if ($myPending > 0): ?>
              &nbsp;|&nbsp;<span class="text-warning"><?= $myPending ?> pending approval</span>
              <?php endif; ?>
            </p>
          </div>
          <a href="<?= $u ?>/api/attendance/export.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>&teacher_id=<?= $teacherId ?>"
             class="btn btn-sm btn-success">Export CSV</a>
        </div>

        <!-- Today's Attendance with Face Previews -->
        <div class="card mb-4">
          <div class="card-header fw-semibold">Today's Attendance — Face Previews</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Student Name</th>
                    <th>Section</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <th>Face In</th>
                    <th>Face Out</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($todayRows)): ?>
                  <tr><td colspan="7" class="text-center text-body-secondary py-4">No attendance records for today yet.</td></tr>
                  <?php else: ?>
                  <?php foreach ($todayRows as $log):
                    $badge = match($log['status']) {
                      'present' => 'success', 'late' => 'warning', 'absent' => 'danger', default => 'secondary'
                    };
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($log['student_name']) ?></td>
                    <td><?= htmlspecialchars($log['grade_section']) ?></td>
                    <td><?= htmlspecialchars($log['time_in'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($log['time_out'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= $badge ?> text-capitalize"><?= htmlspecialchars($log['status']) ?></span></td>
                    <td>
                      <?php if ($log['face_image']): ?>
                      <img src="<?= $u ?>/<?= htmlspecialchars($log['face_image']) ?>" class="face-thumb"
                           onclick="showFaceModal(this.src,'<?= htmlspecialchars($log['student_name']) ?> — Time In')" alt="Face In">
                      <?php else: ?>
                      <span class="face-placeholder" title="No photo">📷</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($log['face_image_out']): ?>
                      <img src="<?= $u ?>/<?= htmlspecialchars($log['face_image_out']) ?>" class="face-thumb"
                           onclick="showFaceModal(this.src,'<?= htmlspecialchars($log['student_name']) ?> — Time Out')" alt="Face Out">
                      <?php else: ?>
                      <span class="face-placeholder" title="No photo">📷</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
          <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
              <div class="col-sm-4">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $dateFrom ?>">
              </div>
              <div class="col-sm-4">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $dateTo ?>">
              </div>
              <div class="col-sm-2">
                <button class="btn btn-primary btn-sm w-100">Generate</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Chart -->
        <div class="card mb-4">
          <div class="card-header fw-semibold">Daily Attendance Trend — My Class</div>
          <div class="card-body"><canvas id="reportChart" height="90"></canvas></div>
        </div>

        <!-- Per-student summary -->
        <div class="card mb-4">
          <div class="card-header fw-semibold">Per-Student Summary</div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="d-none d-sm-table-cell">LRN</th>
                    <th>Student Name</th>
                    <th class="d-none d-md-table-cell">Section</th>
                    <th class="text-success">Present</th>
                    <th class="text-warning">Late</th>
                    <th class="d-none d-sm-table-cell text-danger">Absent</th>
                    <th class="d-none d-md-table-cell">Total</th>
                    <th>%</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($rows)): ?>
                  <tr><td colspan="8" class="text-center text-body-secondary py-4">No students or attendance data found.</td></tr>
                  <?php else: ?>
                  <?php foreach ($rows as $r):
                    $pct = $r['cnt_total'] > 0 ? round(($r['cnt_present'] + $r['cnt_late']) / $r['cnt_total'] * 100, 1) : 0;
                    $pc  = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
                  ?>
                  <tr>
                    <td class="font-monospace small d-none d-sm-table-cell"><?= htmlspecialchars($r['lrn']) ?></td>
                    <td><?= htmlspecialchars($r['student_name']) ?></td>
                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($r['grade_section']) ?></td>
                    <td class="text-success fw-semibold"><?= (int)$r['cnt_present'] ?></td>
                    <td class="text-warning fw-semibold"><?= (int)$r['cnt_late'] ?></td>
                    <td class="text-danger fw-semibold d-none d-sm-table-cell"><?= (int)$r['cnt_absent'] ?></td>
                    <td class="d-none d-md-table-cell"><?= (int)$r['cnt_total'] ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-1">
                        <div class="progress d-none d-sm-flex flex-grow-1" style="height:6px;">
                          <div class="progress-bar bg-<?= $pc ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="small text-<?= $pc ?>"><?= $pct ?>%</span>
                      </div>
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
    <footer class="footer px-4 py-3 border-top">
      <div class="text-body-secondary small">&copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — ATS</div>
    </footer>
  </div>

  <!-- Face preview modal -->
  <div class="modal fade" id="faceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="faceModalLabel">Face Photo</h5>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <img id="faceModalImg" src="" alt="Face" style="max-width:100%;border-radius:12px;">
        </div>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div id="msg-toast" style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;min-width:260px;padding:.85rem 1.25rem;border-radius:10px;color:#fff;font-weight:600;font-size:.9rem;display:none;box-shadow:0 4px 20px rgba(0,0,0,.25);transition:opacity .3s" id="msg-toast">
    <span id="msg-toast-body"></span>
  </div>

  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?= $u ?>/vendors/chart.js/js/chart.umd.js"></script>
  <script>
  new Chart(document.getElementById('reportChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [
        { label:'Present', data:<?= json_encode($chartPresent) ?>, borderColor:'#198754', backgroundColor:'rgba(25,135,84,.15)', tension:.3, fill:true },
        { label:'Late',    data:<?= json_encode($chartLate) ?>,    borderColor:'#ffc107', backgroundColor:'rgba(255,193,7,.15)',  tension:.3, fill:true },
      ]
    },
    options:{ responsive:true, scales:{ y:{ beginAtZero:true, precision:0 } } }
  });

  function showFaceModal(src, title) {
    document.getElementById('faceModalImg').src   = src;
    document.getElementById('faceModalLabel').textContent = title;
    new coreui.Modal(document.getElementById('faceModal')).show();
  }

  document.getElementById('btn-send-msg').addEventListener('click', async () => {
    const msg  = document.getElementById('msg-text').value.trim();
    const type = document.getElementById('msg-type').value;
    if (!msg) { alert('Please enter a message.'); return; }
    const btn = document.getElementById('btn-send-msg');
    btn.disabled = true; btn.textContent = 'Sending…';
    try {
      const res  = await fetch('<?= $u ?>/api/messages/send.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({message: msg, type})
      });
      const data = await res.json();
      showToast(data.success ? '✅ Message sent to kiosk!' : ('❌ ' + (data.message||'Failed')), data.success ? 'bg-success' : 'bg-danger');
      if (data.success) document.getElementById('msg-text').value = '';
    } catch(e) { showToast('❌ Network error', 'bg-danger'); }
    btn.disabled = false; btn.textContent = 'Send to Kiosk';
  });

  function showToast(text, cls) {
    const el = document.getElementById('msg-toast');
    el.style.background = cls === 'bg-success' ? '#198754' : '#dc3545';
    document.getElementById('msg-toast-body').textContent = text;
    el.style.display = 'block'; el.style.opacity = '1';
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => { el.style.display = 'none'; }, 300); }, 3500);
  }
  </script>
</body>
</html>
