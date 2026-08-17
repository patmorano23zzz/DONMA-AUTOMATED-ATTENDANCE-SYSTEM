<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// This page is shown after teacher self-registration — redirect logged-in users away
if (!empty($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
require_once __DIR__ . '/../config/db.php';
$u = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check Your Email | DONMA ATS</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link href="<?= $u ?>/css/style.css" rel="stylesheet">
  <script src="<?= $u ?>/js/config.js"></script>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
  <div class="container text-center" style="max-width:420px">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="text-primary mb-3" width="56">
      <path fill="currentColor" d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/>
    </svg>
    <h1 class="h4 fw-bold mb-2">Check Your Email</h1>
    <p class="text-body-secondary mb-4">
      Your registration has been submitted. An administrator will review and approve your account.
      You will be able to log in once approved.
    </p>
    <a href="<?= $u ?>/authentication/login.php?force=1" class="btn btn-primary">Back to Login</a>
  </div>
  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
</body>
</html>
