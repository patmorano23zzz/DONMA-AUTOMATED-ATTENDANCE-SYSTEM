<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../authentication/login.php'); exit; }

require_once __DIR__ . '/../config/db.php';

$pdo      = getDB();
$u        = APP_URL;
$userRole = $_SESSION['role'];
$today    = date('Y-m-d');

// Filters
$filterDate    = $_GET['date']    ?? $today;
$filterSection = $_GET['section'] ?? '';
$filterStatus  = $_GET['status']  ?? '';
$search        = trim($_GET['q']  ?? '');

// Section list for dropdown
$defaultSections = [
    'Grade 1 - Mabini','Grade 1 - Rizal','Grade 2 - Mabini','Grade 2 - Rizal',
    'Grade 3 - Mabini','Grade 3 - Rizal','Grade 4 - Mabini','Grade 4 - Rizal',
    'Grade 5 - Mabini','Grade 5 - Rizal','Grade 6 - Mabini','Grade 6 - Rizal',
];
$dbSections = $pdo->query('SELECT DISTINCT grade_section FROM students ORDER BY grade_section')->fetchAll(PDO::FETCH_COLUMN);
$sections   = array_unique(array_merge($defaultSections, $dbSections));
sort($sections);

// Build query
$where  = ['DATE(a.time_in) = :date'];
$params = [':date' => $filterDate];

if ($filterSection !== '') {
    $where[]          = 's.grade_section = :section';
    $params[':section'] = $filterSection;
}
if ($filterStatus !== '') {
    $where[]         = 'a.status = :status';
    $params[':status'] = $filterStatus;
}
if ($search !== '') {
    $where[]      = "(s.lrn LIKE :q OR s.first_name LIKE :q OR s.last_name LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

// Teacher: scope to only their enrolled students (by teacher_id, not just section)
if ($userRole === 'teacher') {
    $where[]             = 's.teacher_id = :my_tid';
    $params[':my_tid']   = (int)$_SESSION['user_id'];
}

$sql = 'SELECT a.id,
               s.lrn,
               CONCAT(s.last_name,", ",s.first_name) AS student_name,
               s.grade_section,
               a.time_in,
               a.time_out,
               a.status,
               a.face_image
        FROM attendance a
        JOIN students s ON s.id = a.student_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY a.time_in DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$activePage = 'attendance';
$pageTitle  = 'Attendance Records';
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <style>
    @media(max-width:575px){
      /* Hide less critical columns on mobile */
      .table .d-mobile-none { display:none !important; }
      /* Tighter filter form */
      .card-body .row.g-2 { gap:.4rem !important; }
      /* Compress export button */
      .btn-success.btn-sm { font-size:.72rem; padding:.3rem .6rem; }
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
            <li class="breadcrumb-item active">Attendance Records</li>
          </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Attendance Records</h4>
          <a href="<?= $u ?>/api/attendance/export.php?<?= htmlspecialchars(http_build_query($_GET)) ?>"
             class="btn btn-sm btn-success">Export CSV</a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
          <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
              <div class="col-sm-3">
                <label class="form-label small mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm"
                  value="<?= htmlspecialchars($filterDate) ?>">
              </div>
              <div class="col-sm-3">
                <label class="form-label small mb-1">Section</label>
                <input type="text" name="section" list="section-list"
                  class="form-control form-control-sm" autocomplete="off"
                  placeholder="All Sections"
                  value="<?= htmlspecialchars($filterSection) ?>">
                <datalist id="section-list">
                  <option value="Grade 1"><option value="Grade 2">
                  <option value="Grade 3"><option value="Grade 4">
                  <option value="Grade 5"><option value="Grade 6">
                  <?php foreach ($dbSections as $sec): ?><option value="<?= htmlspecialchars($sec) ?>"><?php endforeach; ?>
                </datalist>
              </div>
              <div class="col-sm-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                  <option value="">All</option>
                  <option value="present" <?= $filterStatus === 'present' ? 'selected' : '' ?>>Present</option>
                  <option value="late"    <?= $filterStatus === 'late'    ? 'selected' : '' ?>>Late</option>
                  <option value="absent"  <?= $filterStatus === 'absent'  ? 'selected' : '' ?>>Absent</option>
                </select>
              </div>
              <div class="col-sm-3">
                <label class="form-label small mb-1">Search (Name / LRN)</label>
                <input type="text" name="q" class="form-control form-control-sm"
                  placeholder="Search…" value="<?= htmlspecialchars($search) ?>">
              </div>
              <div class="col-sm-1">
                <button class="btn btn-primary btn-sm w-100" type="submit">Filter</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Table -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
              <?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?>
              &mdash; <?= date('F j, Y', strtotime($filterDate)) ?>
            </span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="d-mobile-none">#</th>
                    <th class="d-mobile-none">LRN</th>
                    <th>Student Name</th>
                    <th class="d-mobile-none">Grade / Section</th>
                    <th>Time In</th>
                    <th class="d-mobile-none">Time Out</th>
                    <th>Status</th>
                    <th class="d-mobile-none">Face</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($records)): ?>
                  <tr>
                    <td colspan="8" class="text-center text-body-secondary py-4">No records found.</td>
                  </tr>
                  <?php else: ?>
                  <?php foreach ($records as $i => $r): ?>
                  <tr>
                    <td class="text-body-secondary small d-mobile-none"><?= $i + 1 ?></td>
                    <td class="font-monospace small d-mobile-none"><?= htmlspecialchars($r['lrn']) ?></td>
                    <td><?= htmlspecialchars($r['student_name']) ?></td>
                    <td class="d-mobile-none"><?= htmlspecialchars($r['grade_section']) ?></td>
                    <td><?= $r['time_in']  ? date('h:i A', strtotime($r['time_in']))  : '—' ?></td>
                    <td class="d-mobile-none"><?= $r['time_out'] ? date('h:i A', strtotime($r['time_out'])) : '—' ?></td>
                    <td>
                      <?php $badge = match($r['status']) {
                        'present' => 'success', 'late' => 'warning', 'absent' => 'danger', default => 'secondary'
                      }; ?>
                      <span class="badge bg-<?= $badge ?> text-capitalize"><?= htmlspecialchars($r['status']) ?></span>
                    </td>
                    <td class="d-mobile-none">
                      <?php if ($r['face_image']): ?>
                      <img src="<?= $u ?>/<?= htmlspecialchars($r['face_image']) ?>"
                           width="36" height="36" class="rounded-circle object-fit-cover border"
                           alt="Face" style="cursor:pointer;"
                           data-coreui-toggle="tooltip"
                           title="Face captured">
                      <?php else: ?>
                      <span class="text-body-secondary small">—</span>
                      <?php endif; ?>
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

  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
  <script>
    document.querySelectorAll('[data-coreui-toggle="tooltip"]').forEach(el => new coreui.Tooltip(el));
  </script>
</body>
</html>
