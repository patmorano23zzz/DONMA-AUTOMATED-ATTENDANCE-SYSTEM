<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
$u       = APP_URL;
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name']      ?? '');
    $email     = trim($_POST['email']     ?? '');
    $teacherId = trim($_POST['teacher_id'] ?? '');
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm']        ?? '';
    $terms     = $_POST['terms']          ?? '';

    if (!$name || !$email || !$teacherId || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!$terms) {
        $error = 'You must accept the terms and conditions.';
    } else {
        $pdo = getDB();
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? OR teacher_id = ? LIMIT 1');
        $chk->execute([$email, $teacherId]);
        if ($chk->fetch()) {
            $error = 'An account with this email or Teacher ID already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare(
                'INSERT INTO users (name, email, teacher_id, role, password_hash, is_active, created_at)
                 VALUES (?, ?, ?, "teacher", ?, 0, NOW())'
            );
            $ins->execute([$name, $email, $teacherId, $hash]);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Teacher Registration | DONMA ATS</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link rel="stylesheet" href="<?= $u ?>/vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?= $u ?>/css/vendors/simplebar.css">
  <link href="<?= $u ?>/css/style.css" rel="stylesheet">
  <script src="<?= $u ?>/js/config.js"></script>
  <script src="<?= $u ?>/js/color-modes.js"></script>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
  <div class="container" style="max-width:30rem">
    <div class="d-flex flex-column gap-4">

      <div class="text-center">
        <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" class="mb-2" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
        <h1 class="h5 fw-bold mb-0">Teacher Registration</h1>
        <p class="text-body-secondary small">Don Marcelo C. Marty Elementary School — ATS</p>
      </div>

      <?php if ($success): ?>
      <div class="card p-4">
        <div class="card-body text-center d-flex flex-column gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="text-success mx-auto" width="48">
            <path fill="currentColor" d="M256 48a208 208 0 1 1 0 416A208 208 0 0 1 256 48zm0-48C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0zM369 209 241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/>
          </svg>
          <h2 class="h6">Registration Submitted</h2>
          <p class="text-body-secondary small mb-0">Your account is pending administrator approval. You will be notified once activated.</p>
          <a href="<?= $u ?>/authentication/login.php?force=1" class="btn btn-primary">Back to Login</a>
        </div>
      </div>
      <?php else: ?>
      <div class="card p-4">
        <div class="card-body d-flex flex-column gap-3">
          <h2 class="h6 text-center text-body-secondary text-uppercase fw-semibold">Create Teacher Account</h2>

          <?php if ($error !== ''): ?>
          <div class="alert alert-danger py-2 mb-0"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST" action="<?= $u ?>/authentication/register.php" autocomplete="off" novalidate>
            <div class="mb-3">
              <label class="form-label" for="name">Full Name</label>
              <input class="form-control" id="name" name="name" type="text"
                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="email">Email Address</label>
              <input class="form-control" id="email" name="email" type="email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="teacher_id">Teacher ID</label>
              <input class="form-control" id="teacher_id" name="teacher_id" type="text"
                placeholder="e.g. T-0001"
                value="<?= htmlspecialchars($_POST['teacher_id'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="password">Password</label>
              <input class="form-control" id="password" name="password" type="password"
                placeholder="Min. 8 characters" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="confirm">Confirm Password</label>
              <input class="form-control" id="confirm" name="confirm" type="password" required>
            </div>
            <div class="mb-3">
              <label class="form-check">
                <input class="form-check-input" type="checkbox" name="terms" value="1">
                <span class="form-check-label small">I accept the <a href="#">terms and conditions</a></span>
              </label>
            </div>
            <button class="btn btn-primary w-100" type="submit">Submit Registration</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <div class="text-center text-body-secondary small">
        Already have an account? <a href="<?= $u ?>/authentication/login.php?force=1">Sign in</a>
      </div>
    </div>
  </div>
  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
</body>
</html>