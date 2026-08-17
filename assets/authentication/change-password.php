<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
$u       = APP_URL;
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        $error = 'All fields are required.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($current, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([$hash, (int)$_SESSION['user_id']]);
            $success = true;
        }
    }
}

$activePage = '';
$pageTitle  = 'Change Password';
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
            <li class="breadcrumb-item active">Change Password</li>
          </ol>
        </nav>
        <div class="row justify-content-center">
          <div class="col-lg-5">
            <div class="card">
              <div class="card-header fw-semibold">Change Your Password</div>
              <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success">Password changed successfully.</div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST" action="<?= $u ?>/authentication/change-password.php">
                  <?= csrf_field() ?>
                  <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input class="form-control" type="password" name="current_password" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input class="form-control" type="password" name="new_password" placeholder="Min. 8 characters" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input class="form-control" type="password" name="confirm_password" required>
                  </div>
                  <button class="btn btn-primary w-100" type="submit">Update Password</button>
                </form>
              </div>
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
