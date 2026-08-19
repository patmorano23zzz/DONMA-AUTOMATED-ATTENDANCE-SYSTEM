<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: landing.php'); exit; }

require_once __DIR__ . '/config/db.php';
$u = APP_URL;

$pdo      = getDB();
$today    = date('Y-m-d');
$userRole = $_SESSION['role'];
$userId   = (int)$_SESSION['user_id'];

// -- Stats — scoped by role -------------------------------------------------
if ($userRole === 'teacher') {
    // Only count students assigned to this teacher (approved)
    $totalStudents = $pdo->prepare(
        "SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='approved'"
    );
    $totalStudents->execute([$userId]);
    $totalStudents = (int)$totalStudents->fetchColumn();

    $stmtToday = $pdo->prepare(
        "SELECT COUNT(DISTINCT a.student_id) FROM attendance a
         JOIN students s ON s.id = a.student_id
         WHERE s.teacher_id=? AND s.enrollment_status='approved' AND DATE(a.time_in)=?"
    );
    $stmtToday->execute([$userId, $today]);
    $presentToday = (int)$stmtToday->fetchColumn();

    $stmtLate = $pdo->prepare(
        "SELECT COUNT(*) FROM attendance a
         JOIN students s ON s.id = a.student_id
         WHERE s.teacher_id=? AND s.enrollment_status='approved'
           AND DATE(a.time_in)=? AND a.status='late'"
    );
    $stmtLate->execute([$userId, $today]);
    $lateToday = (int)$stmtLate->fetchColumn();

    $mpStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE teacher_id=? AND enrollment_status='pending'");
    $mpStmt->execute([$userId]);
    $myPending = (int)$mpStmt->fetchColumn();

} else {
    // Admin sees school-wide stats
    $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE enrollment_status='approved'")->fetchColumn();

    $stmtToday = $pdo->prepare('SELECT COUNT(DISTINCT student_id) FROM attendance WHERE DATE(time_in)=?');
    $stmtToday->execute([$today]);
    $presentToday = (int)$stmtToday->fetchColumn();

    $stmtLate = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE DATE(time_in)=? AND status='late'");
    $stmtLate->execute([$today]);
    $lateToday = (int)$stmtLate->fetchColumn();

    $myPending = 0;
}

$absentToday = max(0, $totalStudents - $presentToday);

// Pending enrollment count (admin)
$pendingEnrollments = 0;
if ($userRole === 'admin') {
    try {
        $pendingEnrollments = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE enrollment_status='pending'")->fetchColumn();
    } catch(Exception $e) {}
}

// Teacher: my student count (same as totalStudents above)
$myStudentCount = ($userRole === 'teacher') ? $totalStudents : 0;

// -- Recent attendance — scoped by role -----------------------------------
if ($userRole === 'teacher') {
    $stmtRecent = $pdo->prepare(
        "SELECT a.id, s.lrn, CONCAT(s.last_name,', ',s.first_name) AS student_name,
                s.grade_section, a.time_in, a.time_out, a.status, a.face_image
         FROM attendance a
         JOIN students s ON s.id = a.student_id
         WHERE s.teacher_id=? AND s.enrollment_status='approved' AND DATE(a.time_in)=?
         ORDER BY a.time_in DESC LIMIT 20"
    );
    $stmtRecent->execute([$userId, $today]);
} else {
    $stmtRecent = $pdo->prepare(
        "SELECT a.id, s.lrn, CONCAT(s.last_name,', ',s.first_name) AS student_name,
                s.grade_section, a.time_in, a.time_out, a.status, a.face_image
         FROM attendance a
         JOIN students s ON s.id = a.student_id
         WHERE DATE(a.time_in)=?
         ORDER BY a.time_in DESC LIMIT 20"
    );
    $stmtRecent->execute([$today]);
}
$recentLogs = $stmtRecent->fetchAll();

// -- Weekly chart data — scoped by role ------------------------------------
$weekDays   = [];
$weekCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    if ($userRole === 'teacher') {
        $s = $pdo->prepare(
            "SELECT COUNT(DISTINCT a.student_id) FROM attendance a
             JOIN students s ON s.id=a.student_id
             WHERE s.teacher_id=? AND s.enrollment_status='approved' AND DATE(a.time_in)=?"
        );
        $s->execute([$userId, $d]);
    } else {
        $s = $pdo->prepare('SELECT COUNT(DISTINCT student_id) FROM attendance WHERE DATE(time_in)=?');
        $s->execute([$d]);
    }
    $weekDays[]   = date('D M j', strtotime($d));
    $weekCounts[] = (int)$s->fetchColumn();
}

$activePage = 'dashboard';
$pageTitle  = 'Dashboard';
$base       = './';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/head.php'; ?>
  <link href="<?= $u ?>/vendors/@coreui/chartjs/css/coreui-chartjs.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="body flex-grow-1">
      <div class="container-lg px-4">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item active">Dashboard</li>
          </ol>
        </nav>

        <h4 class="mb-3 mb-sm-4" style="font-size:clamp(1rem,4vw,1.35rem);line-height:1.3;">
          <?php if ($userRole === 'teacher'): ?>
            My Class Overview
          <?php else: ?>
            School Attendance Overview
          <?php endif; ?>
          <span class="d-none d-sm-inline">— <?= date('F j, Y') ?></span>
          <div class="d-sm-none text-body-secondary" style="font-size:.78rem;font-weight:400;"><?= date('M j, Y') ?></div>
          <?php if ($userRole === 'teacher' && $myPending > 0): ?>
          <span class="badge bg-warning ms-1" style="font-size:.65rem;">
            <?= $myPending ?> pending
          </span>
          <?php endif; ?>
        </h4>

        <!-- Stats cards -->
        <div class="row g-2 g-sm-4 mb-4">
          <div class="col-6 col-xl-3">
            <div class="card text-white bg-primary">
              <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                  <div class="fs-2 fs-sm-1 fw-semibold"><?= $totalStudents ?></div>
                  <div class="small" style="font-size:.75rem;"><?= $userRole === 'teacher' ? 'My Students' : 'Total Students' ?></div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="icon icon-2xl icon-sm-4xl opacity-50 d-none d-sm-block">
                  <path fill="currentColor" d="M256 48C141.1 48 48 141.1 48 256s93.1 208 208 208 208-93.1 208-208S370.9 48 256 48zm0 80c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64zm0 272c-52.9 0-100.2-24.3-131.5-62.4C145.6 310 199 288 256 288s110.4 22 131.5 49.6C356.2 375.7 308.9 400 256 400z"/>
                </svg>
              </div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="card text-white bg-success">
              <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                  <div class="fs-2 fs-sm-1 fw-semibold"><?= $presentToday ?></div>
                  <div class="small" style="font-size:.75rem;">Present Today</div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="icon icon-2xl opacity-50 d-none d-sm-block">
                  <path fill="currentColor" d="M256 48a208 208 0 1 1 0 416A208 208 0 0 1 256 48zm0-48C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0zM369 209 241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/>
                </svg>
              </div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="card text-white bg-warning">
              <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                  <div class="fs-2 fs-sm-1 fw-semibold"><?= $lateToday ?></div>
                  <div class="small" style="font-size:.75rem;">Late Today</div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="icon icon-2xl opacity-50 d-none d-sm-block">
                  <path fill="currentColor" d="M256 48a208 208 0 1 1 0 416A208 208 0 0 1 256 48zm0-48C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0zm24 128h-48v136l88 52 24-40-64-38V128z"/>
                </svg>
              </div>
            </div>
          </div>
          <div class="col-6 col-xl-3">
            <div class="card text-white bg-danger">
              <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div>
                  <div class="fs-2 fs-sm-1 fw-semibold"><?= $absentToday ?></div>
                  <div class="small" style="font-size:.75rem;">Absent Today</div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="icon icon-2xl opacity-50 d-none d-sm-block">
                  <path fill="currentColor" d="M256 48a208 208 0 1 1 0 416A208 208 0 0 1 256 48zm0-48C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0zm-88 232h176v48H168v-48z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4 mb-4">
          <!-- Weekly chart -->

        <?php if ($userRole === 'admin' && $pendingEnrollments > 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-3 mb-0" style="border-radius:.75rem;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="22" flex-shrink="0">
            <path fill="currentColor" d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480H40c-14.3 0-27.6-7.5-34.7-19.8s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24v112c0 13.3 10.7 24 24 24s24-10.7 24-24V184c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0-64 0 32 32 0 0 0 64 0z"/>
          </svg>
          <div class="flex-grow-1">
            <strong><?= $pendingEnrollments ?> student enrollment<?= $pendingEnrollments > 1 ? 's' : '' ?></strong> submitted by teachers <?= $pendingEnrollments > 1 ? 'are' : 'is' ?> waiting for your approval.
          </div>
          <a href="admin/pending-enrollments.php" class="btn btn-warning btn-sm">Review Now →</a>
        </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
          <!-- Weekly chart -->
          <div class="col-lg-7">
            <div class="card h-100">
              <div class="card-header fw-semibold">Weekly Attendance (Last 7 Days)</div>
              <div class="card-body">
                <canvas id="weeklyChart" height="120"></canvas>
              </div>
            </div>
          </div>

          <!-- Quick actions -->
          <div class="col-lg-5">
            <div class="card h-100">
              <div class="card-header fw-semibold">Quick Actions</div>
              <div class="card-body d-flex flex-column gap-2">
                <a href="kiosk.php" class="btn btn-primary">
                  Open Student Kiosk
                </a>
                <a href="attendance/records.php" class="btn btn-outline-secondary">
                  View Today's Attendance
                </a>
                <?php if ($userRole === 'teacher'): ?>
                <a href="teacher/reports.php" class="btn btn-outline-secondary">
                  My Class Report
                </a>
                <?php if ($myStudentCount > 0): ?>
                <div class="alert alert-info py-2 mb-0 small">
                  You have <strong><?= $myStudentCount ?></strong> approved student<?= $myStudentCount !== 1 ? 's' : '' ?> in your class.
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($userRole === 'admin'): ?>
                <?php if ($pendingEnrollments > 0): ?>
                <a href="admin/pending-enrollments.php" class="btn btn-warning">
                  ⚠ <?= $pendingEnrollments ?> Pending Enrollment<?= $pendingEnrollments > 1 ? 's' : '' ?>
                </a>
                <?php endif; ?>
                <a href="admin/reports.php" class="btn btn-outline-secondary">
                  Generate Report
                </a>
                <a href="admin/users.php" class="btn btn-outline-warning">
                  Manage Users / Teachers
                </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent attendance log -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Today's Attendance Log</span>
            <a href="attendance/records.php" class="btn btn-sm btn-outline-primary">View All</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                  <tr>
                    <th>LRN</th>
                    <th>Student Name</th>
                    <th>Grade / Section</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                    <?php if ($userRole === 'teacher'): ?><th>Face</th><?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentLogs)): ?>
                  <tr><td colspan="6" class="text-center text-body-secondary py-4">No attendance records for today yet.</td></tr>
                  <?php else: ?>
                  <?php foreach ($recentLogs as $log): ?>
                  <tr>
                    <td><?= htmlspecialchars($log['lrn']) ?></td>
                    <td><?= htmlspecialchars($log['student_name']) ?></td>
                    <td><?= htmlspecialchars($log['grade_section']) ?></td>
                    <td><?= htmlspecialchars($log['time_in'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($log['time_out'] ?? '—') ?></td>
                    <td>
                      <?php
                        $badge = match($log['status']) {
                          'present' => 'success',
                          'late'    => 'warning',
                          'absent'  => 'danger',
                          default   => 'secondary',
                        };
                      ?>
                      <span class="badge bg-<?= $badge ?> text-capitalize"><?= htmlspecialchars($log['status']) ?></span>
                    </td>
                    <?php if ($userRole === 'teacher'): ?>
                    <td>
                      <?php if (!empty($log['face_image'])): ?>
                      <img src="<?= $u ?>/<?= htmlspecialchars($log['face_image']) ?>" alt="face"
                           style="width:40px;height:40px;border-radius:8px;object-fit:cover;cursor:pointer;border:2px solid #dee2e6;"
                           onclick="showFaceModal(this.src,'<?= htmlspecialchars(addslashes($log['student_name'])) ?>')">
                      <?php else: ?>
                      <span style="font-size:1.2rem;color:#aaa;">📷</span>
                      <?php endif; ?>
                    </td>
                    <?php endif; ?>
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
      <div class="text-body-secondary small">
        &copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — Automated Attendance Tracking System
      </div>
    </footer>
  </div>

  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?= $u ?>/vendors/chart.js/js/chart.umd.js"></script>

  <?php if ($userRole === 'teacher'): ?>
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
  <script>
  function showFaceModal(src, title) {
    document.getElementById('faceModalImg').src = src;
    document.getElementById('faceModalLabel').textContent = title;
    new coreui.Modal(document.getElementById('faceModal')).show();
  }
  </script>
  <?php endif; ?>
  <script>
    const ctx = document.getElementById('weeklyChart');
    if (ctx) {
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?= json_encode($weekDays) ?>,
          datasets: [{
            label: 'Students Present',
            data: <?= json_encode($weekCounts) ?>,
            backgroundColor: 'rgba(13,110,253,0.7)',
            borderRadius: 4,
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, precision: 0 } }
        }
      });
    }
  </script>

  <script>
  // ── Live polling — updates stat cards every 10s (safe for InfinityFree) ──
  const POLL_URL      = '<?= $u ?>/api/attendance/poll.php';
  const POLL_INTERVAL = 10000; // 10 seconds — InfinityFree safe minimum
  let   pollFailCount = 0;

  // Map stat card elements
  const statEls = {
    total:   document.querySelector('.bg-primary .fs-1'),
    present: document.querySelector('.bg-success .fs-1'),
    late:    document.querySelector('.bg-warning .fs-1'),
    absent:  document.querySelector('.bg-danger  .fs-1'),
  };

  // Pending badge in heading (if exists)
  const pendingBadge = document.querySelector('h4 .badge.bg-warning');

  function animateValue(el, newVal) {
    if (!el) return;
    const cur = parseInt(el.textContent) || 0;
    if (cur === newVal) return;
    el.style.transition = 'opacity .25s';
    el.style.opacity = '0';
    setTimeout(() => {
      el.textContent = newVal;
      el.style.opacity = '1';
    }, 150);
  }

  async function pollStats() {
    try {
      const res  = await fetch(POLL_URL, { credentials: 'same-origin' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      pollFailCount = 0;

      animateValue(statEls.total,   data.total);
      animateValue(statEls.present, data.present);
      animateValue(statEls.late,    data.late);
      animateValue(statEls.absent,  data.absent);

      if (pendingBadge && data.pending > 0) {
        pendingBadge.textContent = data.pending + ' enrollment' + (data.pending > 1 ? 's' : '') + ' pending approval';
        pendingBadge.style.display = '';
      } else if (pendingBadge) {
        pendingBadge.style.display = 'none';
      }

    } catch (e) {
      pollFailCount++;
      // Back off: if 3+ consecutive failures, stop polling to avoid hammering the server
      if (pollFailCount >= 3) {
        console.warn('DONMA poll: too many failures, stopping. Reload page to resume.');
        clearInterval(pollTimer);
      }
    }
  }

  // Start polling after 10s initial delay (page just loaded fresh data)
  const pollTimer = setInterval(pollStats, POLL_INTERVAL);
  </script>
</body>
</html>
