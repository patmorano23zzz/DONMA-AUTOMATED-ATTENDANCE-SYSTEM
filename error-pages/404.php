<?php
if (session_status() === PHP_SESSION_NONE) session_start();
http_response_code(404);
$isLoggedIn = !empty($_SESSION['user_id']);
$appUrl = '';
// Determine APP_URL without full db.php to avoid errors
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = str_replace('\\','/',realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs'));
$appRoot = str_replace('\\','/',realpath(__DIR__ . '/..'));
$appUrl  = $scheme . '://' . $host . str_replace($docRoot, '', $appRoot);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 — Page Not Found | DONMA ATS</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $appUrl ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link href="<?= $appUrl ?>/css/style.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, #0d1b2a 0%, #1a3a6b 100%);
      color: #fff; padding: 2rem;
    }
    .error-wrap { text-align: center; animation: fadeUp .6s ease both; }
    .error-code {
      font-size: clamp(6rem, 20vw, 10rem); font-weight: 900; line-height: 1;
      background: linear-gradient(135deg, #3b82f6, #93c5fd);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      animation: float 3s ease-in-out infinite;
    }
    .error-title { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 .5rem; }
    .error-msg   { color: rgba(255,255,255,.6); font-size: .95rem; margin-bottom: 2rem; max-width: 380px; margin-inline: auto; }
    .btn-group   { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
    .btn {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .7rem 1.6rem; border-radius: 50px; font-weight: 600;
      font-size: .9rem; text-decoration: none; border: 2px solid transparent;
      transition: transform .2s, box-shadow .2s;
    }
    .btn:hover { transform: translateY(-2px); }
    .btn-primary { background: #1a56db; color: #fff; }
    .btn-primary:hover { background: #1044b8; box-shadow: 0 6px 20px rgba(26,86,219,.4); }
    .btn-outline { background: transparent; color: #fff; border-color: rgba(255,255,255,.35); }
    .btn-outline:hover { background: rgba(255,255,255,.1); }
    .school-label { margin-top: 2.5rem; font-size: .75rem; color: rgba(255,255,255,.3); }
  </style>
</head>
<body>
  <div class="error-wrap">
    <!-- School icon -->
    <img src="<?= $appUrl ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" style="width:52px;height:52px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">
    <div class="error-code">404</div>
    <div class="error-title">Page Not Found</div>
    <p class="error-msg">
      The page you're looking for doesn't exist or may have been moved.
    </p>

    <div class="btn-group">
      <?php if ($isLoggedIn): ?>
      <a href="<?= $appUrl ?>/index.php" class="btn btn-primary">← Back to Dashboard</a>
      <?php else: ?>
      <a href="<?= $appUrl ?>/landing.php" class="btn btn-primary">← Back to Home</a>
      <a href="<?= $appUrl ?>/authentication/login.php?force=1" class="btn btn-outline">Login</a>
      <?php endif; ?>
      <a href="<?= $appUrl ?>/kiosk.php" class="btn btn-outline">Student Kiosk</a>
    </div>

    <div class="school-label">Don Marcelo C. Marty Elementary School — ATS</div>
  </div>
</body>
</html>