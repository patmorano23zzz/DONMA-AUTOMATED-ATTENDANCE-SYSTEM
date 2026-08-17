<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php'); exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
$pdo = getDB();

// Handle approve/deactivate/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(); // Block forged cross-site requests
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['uid'] ?? 0);
    if ($uid > 0) {
        match($action) {
            'approve'    => $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ?')->execute([$uid]),
            'deactivate' => $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$uid]),
            'delete'     => $pdo->prepare('DELETE FROM users WHERE id = ? AND role != "admin"')->execute([$uid]),
            default      => null
        };
        $_SESSION['flash'] = match($action) {
            'approve'    => ['type' => 'success', 'msg' => 'Account approved and activated.'],
            'deactivate' => ['type' => 'warning', 'msg' => 'Account has been deactivated.'],
            'delete'     => ['type' => 'danger',  'msg' => 'User deleted successfully.'],
            default      => ['type' => 'info',    'msg' => 'Action completed.'],
        };
    }
    header('Location: users.php'); exit;
}

$users = $pdo->query('SELECT * FROM users ORDER BY role, name')->fetchAll();

$activePage = 'users';
$pageTitle  = 'Users & Teachers';
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
            <li class="breadcrumb-item active">Users & Teachers</li>
          </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">Users & Teachers</h4>
          <a href="add-user.php" class="btn btn-primary btn-sm">+ Add User</a>
        </div>

        <!-- Pending approvals banner -->
        <?php
        $pending = array_filter($users, fn($usr) => !$usr['is_active'] && $usr['role'] === 'teacher');
        if (count($pending) > 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20"><path fill="currentColor" d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480H40c-14.3 0-27.6-7.5-34.7-19.8s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24v112c0 13.3 10.7 24 24 24s24-10.7 24-24V184c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0-64 0 32 32 0 0 0 64 0z"/></svg>
          <strong><?= count($pending) ?> teacher account<?= count($pending) > 1 ? 's' : '' ?> pending approval.</strong>
        </div>
        <?php endif; ?>

        <div class="card">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Name</th>
                    <th class="d-none d-sm-table-cell">Email</th>
                    <th class="d-none d-md-table-cell">Teacher ID</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">Registered</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $usr): ?>
                  <tr>
                    <td><?= htmlspecialchars($usr['name']) ?></td>
                    <td class="d-none d-sm-table-cell"><?= htmlspecialchars($usr['email']) ?></td>
                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($usr['teacher_id'] ?? '—') ?></td>
                    <td>
                      <span class="badge bg-<?= $usr['role'] === 'admin' ? 'danger' : 'info' ?> text-capitalize">
                        <?= htmlspecialchars($usr['role']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-<?= $usr['is_active'] ? 'success' : 'warning' ?>">
                        <?= $usr['is_active'] ? 'Active' : 'Pending' ?>
                      </span>
                    </td>
                    <td class="small text-body-secondary d-none d-md-table-cell">
                      <?= $usr['created_at'] ? date('M j, Y', strtotime($usr['created_at'])) : '—' ?>
                    </td>
                    <td>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="uid" value="<?= $usr['id'] ?>">
                        <?= csrf_field() ?>
                        <?php if (!$usr['is_active']): ?>
                        <button name="action" value="approve" class="btn btn-sm btn-success py-0 px-2">Approve</button>
                        <?php else: ?>
                        <button name="action" value="deactivate" class="btn btn-sm btn-warning py-0 px-2">Deactivate</button>
                        <?php endif; ?>
                        <?php if ($usr['role'] !== 'admin'): ?>
                        <button type="button"
                          class="btn btn-sm btn-outline-danger py-0 px-2"
                          onclick="DonmaModal.confirm({
                            title: 'Delete User',
                            message: 'This will permanently remove <strong><?= htmlspecialchars(addslashes($usr['name'])) ?></strong>. This cannot be undone.',
                            confirmText: 'Yes, Delete',
                            cancelText: 'Cancel',
                            type: 'danger',
                            onConfirm: () => {
                              const f = this.closest('form');
                              const i = document.createElement('input');
                              i.type='hidden'; i.name='action'; i.value='delete';
                              f.appendChild(i); f.submit();
                            }
                          })">Delete</button>
                        <?php endif; ?>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
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
</body>
</html>
