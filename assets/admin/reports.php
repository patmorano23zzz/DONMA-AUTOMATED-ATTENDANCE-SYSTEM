<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php'); exit;
}

require_once __DIR__ . '/../config/db.php';
$pdo = getDB();
$u   = APP_URL;

$dateFrom      = $_GET['from']       ?? date('Y-m-01');
$dateTo        = $_GET['to']         ?? date('Y-m-d');
$section       = $_GET['section']    ?? '';
$filterTeacher = (int)($_GET['teacher_id'] ?? 0);

$defaultSections = [
    'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
];
$dbSections = $pdo->query('SELECT DISTINCT grade_section FROM students ORDER BY grade_section')->fetchAll(PDO::FETCH_COLUMN);
$sections   = array_unique(array_merge($defaultSections, $dbSections));
sort($sections);

// Teachers list for filter dropdown
$teachers = $pdo->query("SELECT id, name, section FROM users WHERE role='teacher' AND is_active=1 ORDER BY name")->fetchAll();

// Build WHERE — always approved only
$where  = ["DATE(a.time_in) BETWEEN :from AND :to", "s.enrollment_status='approved'"];
$params = [':from' => $dateFrom, ':to' => $dateTo];
if ($section !== '') {
    $where[] = 's.grade_section = :section';
    $params[':section'] = $section;
}
if ($filterTeacher > 0) {
    $where[] = 's.teacher_id = :tid';
    $params[':tid'] = $filterTeacher;
}

// Summary per student — include teacher name
$sql = "SELECT s.lrn,
               CONCAT(s.last_name,', ',s.first_name) AS student_name,
               s.grade_section,
               u.name AS teacher_name,
               SUM(a.status='present') AS cnt_present,
               SUM(a.status='late')    AS cnt_late,
               SUM(a.status='absent')  AS cnt_absent,
               COUNT(a.id)             AS cnt_total
        FROM students s
        LEFT JOIN attendance a ON a.student_id = s.id AND " . implode(' AND ', $where) . "
        LEFT JOIN users u ON u.id = s.teacher_id
        WHERE s.enrollment_status='approved'
        " . ($section !== '' ? "AND s.grade_section = :section2" : "") . "
        " . ($filterTeacher > 0 ? "AND s.teacher_id = :tid2" : "") . "
        GROUP BY s.id
        ORDER BY s.grade_section, s.last_name";

// Rebuild params for double usage in LEFT JOIN + WHERE
$execParams = [':from' => $dateFrom, ':to' => $dateTo];
if ($section !== '') { $execParams[':section'] = $section; $execParams[':section2'] = $section; }
if ($filterTeacher > 0) { $execParams[':tid'] = $filterTeacher; $execParams[':tid2'] = $filterTeacher; }

$stmt = $pdo->prepare($sql);
$stmt->execute($execParams);
$summary = $stmt->fetchAll();

// Daily chart — respects active section and teacher filters
$chartLabels = [];
$chartPresent = [];
$chartLate    = [];
$period = new DatePeriod(
    new DateTime($dateFrom),
    new DateInterval('P1D'),
    (new DateTime($dateTo))->modify('+1 day')
);
foreach ($period as $day) {
    $d = $day->format('Y-m-d');
    $chartWhere  = ["DATE(a.time_in) = :cd", "s.enrollment_status='approved'"];
    $chartParams = [':cd' => $d];
    if ($section !== '') { $chartWhere[] = 's.grade_section = :csec'; $chartParams[':csec'] = $section; }
    if ($filterTeacher > 0) { $chartWhere[] = 's.teacher_id = :ctid'; $chartParams[':ctid'] = $filterTeacher; }
    $r = $pdo->prepare(
        "SELECT SUM(a.status='present') AS p, SUM(a.status='late') AS l
         FROM attendance a JOIN students s ON s.id = a.student_id
         WHERE " . implode(' AND ', $chartWhere)
    );
    $r->execute($chartParams);
    $row = $r->fetch();
    $chartLabels[]  = $day->format('M j');
    $chartPresent[] = (int)($row['p'] ?? 0);
    $chartLate[]    = (int)($row['l'] ?? 0);
}

$activePage = 'reports';
$pageTitle  = 'Reports';
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
            <li class="breadcrumb-item active">Reports</li>
          </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Attendance Report</h4>
          <a href="<?= $u ?>/api/attendance/export.php?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>&section=<?= urlencode($section) ?>&teacher_id=<?= $filterTeacher ?>"
             class="btn btn-sm btn-success">Export CSV</a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
          <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
              <div class="col-sm-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $dateFrom ?>">
              </div>
              <div class="col-sm-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $dateTo ?>">
              </div>
              <div class="col-sm-2">
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
              <div class="col-sm-3">
                <label class="form-label small mb-1">Filter by Teacher</label>
                <select name="teacher_id" class="form-select form-select-sm">
                  <option value="0">— All Teachers —</option>
                  <?php foreach ($teachers as $t): ?>
                  <option value="<?= $t['id'] ?>" <?= $filterTeacher === (int)$t['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['name']) ?>
                    <?= $t['section'] ? ' (' . htmlspecialchars($t['section']) . ')' : '' ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-sm-2">
                <button class="btn btn-primary btn-sm w-100">Generate</button>
              </div>
              <div class="col-sm-1">
                <a href="reports.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
              </div>
            </form>
          </div>
        </div>

        <!-- Chart -->
        <div class="card mb-4">
          <div class="card-header fw-semibold">Daily Attendance Trend</div>
          <div class="card-body">
            <canvas id="reportChart" height="80"></canvas>
          </div>
        </div>

        <!-- Per-student summary table -->
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
                    <th class="d-none d-lg-table-cell">Teacher</th>
                    <th class="text-success">Present</th>
                    <th class="text-warning">Late</th>
                    <th class="d-none d-sm-table-cell text-danger">Absent</th>
                    <th class="d-none d-md-table-cell">Total Days</th>
                    <th>%</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($summary)): ?>
                  <tr><td colspan="9" class="text-center text-body-secondary py-4">No data found for the selected filters.</td></tr>
                  <?php else: ?>
                  <?php foreach ($summary as $r): ?>
                  <?php
                    $pct = $r['cnt_total'] > 0
                      ? round(($r['cnt_present'] + $r['cnt_late']) / $r['cnt_total'] * 100, 1)
                      : 0;
                    $pctClass = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
                  ?>
                  <tr>
                    <td class="font-monospace small d-none d-sm-table-cell"><?= htmlspecialchars($r['lrn']) ?></td>
                    <td><?= htmlspecialchars($r['student_name']) ?></td>
                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($r['grade_section']) ?></td>
                    <td class="small d-none d-lg-table-cell"><?= htmlspecialchars($r['teacher_name'] ?? '—') ?></td>
                    <td class="text-success fw-semibold"><?= (int)$r['cnt_present'] ?></td>
                    <td class="text-warning fw-semibold"><?= (int)$r['cnt_late'] ?></td>
                    <td class="text-danger fw-semibold d-none d-sm-table-cell"><?= (int)$r['cnt_absent'] ?></td>
                    <td class="d-none d-md-table-cell"><?= $r['cnt_total'] ?></td>
                    <td>
                      <div class="d-flex align-items-center gap-1">
                        <div class="progress d-none d-sm-flex flex-grow-1" style="height:6px;">
                          <div class="progress-bar bg-<?= $pctClass ?>"
                               style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="small text-<?= $pctClass ?>"><?= $pct ?>%</span>
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
          { label: 'Present', data: <?= json_encode($chartPresent) ?>, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.15)', tension: .3, fill: true },
          { label: 'Late',    data: <?= json_encode($chartLate) ?>,    borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,.15)',  tension: .3, fill: true },
        ]
      },
      options: { responsive: true, scales: { y: { beginAtZero: true, precision: 0 } } }
    });
  </script>
</body>
</html>
