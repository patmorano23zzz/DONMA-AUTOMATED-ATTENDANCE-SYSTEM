<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
$u = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Reset Password | DONMA ATS</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link rel="stylesheet" href="<?= $u ?>/vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?= $u ?>/css/vendors/simplebar.css">
  <link href="<?= $u ?>/css/style.css" rel="stylesheet">
  <script src="<?= $u ?>/js/config.js"></script>
  <script src="<?= $u ?>/js/color-modes.js"></script>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
<div class="container" style="max-width:28rem">
  <div class="d-flex flex-column gap-4">

    <div class="text-center">
      <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" class="mb-2" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
      <h1 class="h5 fw-bold mb-0">Reset Password</h1>
      <p class="text-body-secondary small">DONMA Attendance Tracking System</p>
    </div>

    <div class="card p-4">
      <div class="card-body d-flex flex-column gap-3">
        <p class="text-body-secondary small text-center mb-0">
          Enter your registered email. Your administrator will send you a temporary password.
        </p>
        <form method="POST" action="<?= $u ?>/authentication/reset-password.php">
          <div class="mb-3">
            <label class="form-label" for="email">Email Address</label>
            <input class="form-control" id="email" name="email" type="email"
              placeholder="your@email.com" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Request Reset</button>
        </form>
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="alert alert-success py-2 mb-0">
          If this email is registered, your administrator has been notified.
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="text-center text-body-secondary small">
      <a href="<?= $u ?>/authentication/login.php?force=1">← Back to Login</a>
    </div>
  </div>
</div>
<script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
</body>
</html>