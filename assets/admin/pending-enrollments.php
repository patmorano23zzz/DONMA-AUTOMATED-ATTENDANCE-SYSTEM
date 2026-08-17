<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php'); exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
$pdo = getDB();
$u   = APP_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $sid    = (int)($_POST['sid'] ?? 0);
    if ($sid > 0) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE students SET enrollment_status='approved' WHERE id=?")->execute([$sid]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Student enrollment approved. They can now register.'];
        } elseif ($action === 'reject') {
            $pdo->prepare("DELETE FROM students WHERE id=? AND enrollment_status='pending'")->execute([$sid]);
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Enrollment rejected and student record removed.'];
        }
    }
    header('Location: pending-enrollments.php'); exit;
}

$pending = $pdo->query(
    "SELECT s.*, u.name AS teacher_name, u.teacher_id AS t_id
     FROM students s
     LEFT JOIN users u ON u.id = s.enrolled_by
     WHERE s.enrollment_status = 'pending'
     ORDER BY s.created_at DESC"
)->fetchAll();

$activePage = 'pending-enrollments';
$pageTitle  = 'Pending Enrollments';
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include __DIR__ . '/../includes/head.php'; ?></head>
<body>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>
    <div class="body flex-grow-1">
      <div class="container-lg px-4">

        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $u ?>/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Pending Enrollments</li>
          </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h4 class="mb-0">Pending Student Enrollments</h4>
            <p class="text-body-secondary small mb-0 mt-1">
              These students were enrolled by teachers and need your approval before they can register.
            </p>
          </div>
          <span class="badge bg-warning fs-6"><?= count($pending) ?> pending</span>
        </div>

        <?php if (empty($pending)): ?>
        <div class="alert alert-success">
          ✅ No pending enrollments. All students are approved.
        </div>
        <?php else: ?>
        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th class="d-none d-sm-table-cell">LRN</th>
                    <th>Student Name</th>
                    <th class="d-none d-md-table-cell">Grade / Section</th>
                    <th class="d-none d-sm-table-cell">Enrolled By</th>
                    <th class="d-none d-md-table-cell">Date Submitted</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pending as $s): ?>
                  <tr>
                    <td class="font-monospace small d-none d-sm-table-cell"><?= htmlspecialchars($s['lrn']) ?></td>
                    <td>
                      <?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?>
                      <?= $s['middle_name'] ? htmlspecialchars(' ' . $s['middle_name'][0] . '.') : '' ?>
                    </td>
                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($s['grade_section']) ?></td>
                    <td class="d-none d-sm-table-cell">
                      <?php if ($s['teacher_name']): ?>
                      <span class="fw-semibold"><?= htmlspecialchars($s['teacher_name']) ?></span><br>
                      <span class="text-body-secondary small"><?= htmlspecialchars($s['t_id'] ?? '') ?></span>
                      <?php else: ?>
                      <span class="text-body-secondary">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="small text-body-secondary d-none d-md-table-cell">
                      <?= date('M j, Y g:i A', strtotime($s['created_at'])) ?>
                    </td>
                    <td>
                      <form method="POST" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="sid" value="<?= $s['id'] ?>">
                        <button name="action" value="approve"
                          class="btn btn-sm btn-success py-0 px-2">✓ Approve</button>
                        <button type="button"
                          class="btn btn-sm btn-outline-danger py-0 px-2"
                          onclick="DonmaModal.confirm({
                            title: 'Reject Enrollment',
                            message: 'This will permanently delete the enrollment for <strong><?= htmlspecialchars(addslashes($s['first_name'] . ' ' . $s['last_name'])) ?></strong>. The student record will be removed.',
                            confirmText: 'Yes, Reject',
                            cancelText: 'Cancel',
                            type: 'danger',
                            onConfirm: () => {
                              const f = this.closest('form');
                              const i = document.createElement('input');
                              i.type='hidden'; i.name='action'; i.value='reject';
                              f.appendChild(i); f.submit();
                            }
                          })">✗ Reject</button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
    <footer class="footer px-4 py-3 border-top">
      <div class="text-body-secondary small">&copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — ATS</div>
    </footer>
  </div>
  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
</body>
</html>
