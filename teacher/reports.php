<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../authentication/login.php'); exit;
}
require_once __DIR__ . '/../config/db.php';
$pdo     = getDB();
$u       = APP_URL;
$teacherId = (int)$_SESSION['user_id'];

$dateFrom = $_GET['from']    ?? date('Y-m-01');
$dateTo   = $_GET['to']      ?? date('Y-m-d');

// Only students assigned to this teacher AND approved
$summary = $pdo->prepare(
    "SELECT s.lrn,
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
     GROUP BY s.id
     ORDER BY s.grade_section, s.last_name"
);
$summary->execute([$dateFrom, $dateTo, $teacherId]);
$rows = $summary->fetchAll();

// My enrolled students count
$myTotal = $pdo->prepare("SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='approved'");
$myTotal->execute([$teacherId]); $myTotal = $myTotal->fetchColumn();

$myPending = $pdo->prepare("SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='pending'");
$myPending->execute([$teacherId]); $myPending = $myPending->fetchColumn();

// Daily chart
$chartLabels = []; $chartPresent = []; $chartLate = [];
$period = new DatePeriod(new DateTime($dateFrom), new DateInterval('P1D'), (new DateTime($dateTo))->modify('+1 day'));
foreach ($period as $day) {
    $d = $day->format('Y-m-d');
    $r = $pdo->prepare(
        "SELECT SUM(a.status='present') AS p, SUM(a.status='late') AS l
         FROM attendance a
         JOIN students s ON s.id = a.student_id
         WHERE s.teacher_id = ? AND DATE(a.time_in) = ?"
    );
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
              Showing only students enrolled in your class.
              <strong><?= $myTotal ?></strong> enrolled
              <?php if ($myPending > 0): ?>
              &nbsp;|&nbsp;<span class="text-warning"><?= $myPending ?> pending approval</span>
              <?php endif; ?>
            </p>
          </div>
          <a href="<?= $u ?>/api/attendance/export.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>&teacher_id=<?= $teacherId ?>"
             class="btn btn-sm btn-success">Export CSV</a>
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
          <div class="card-body">
            <canvas id="reportChart" height="90"></canvas>
          </div>
        </div>

        <!-- Per-student table -->
        <div class="card">
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
                    $pct = $r['cnt_total'] > 0
                      ? round(($r['cnt_present'] + $r['cnt_late']) / $r['cnt_total'] * 100, 1) : 0;
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
  </script>
</body>
</html>
