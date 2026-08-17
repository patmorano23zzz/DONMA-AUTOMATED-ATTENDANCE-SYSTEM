<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config/db.php';
$u = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Password Changed | DONMA ATS</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link href="<?= $u ?>/css/style.css" rel="stylesheet">
  <script src="<?= $u ?>/js/config.js"></script>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
  <div class="container text-center" style="max-width:380px">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="text-success mb-3" width="56">
      <path fill="currentColor" d="M256 48a208 208 0 1 1 0 416A208 208 0 0 1 256 48zm0-48C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0zM369 209 241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/>
    </svg>
    <h1 class="h4 fw-bold mb-2">Password Changed!</h1>
    <p class="text-body-secondary mb-4">Your password has been updated successfully.</p>
    <a href="<?= $u ?>/index.php" class="btn btn-primary">Go to Dashboard</a>
  </div>
  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
</body>
</html>
