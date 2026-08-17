<?php
http_response_code(500);
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
  <title>500 — Server Error | DONMA ATS</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $appUrl ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link href="<?= $appUrl ?>/css/style.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    @keyframes pulse  { 0%,100%{opacity:1} 50%{opacity:.5} }
    @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, #1a0a0a 0%, #3b1212 100%);
      color: #fff; padding: 2rem;
    }
    .error-wrap { text-align: center; animation: fadeUp .6s ease both; }
    .error-code {
      font-size: clamp(6rem, 20vw, 10rem); font-weight: 900; line-height: 1;
      background: linear-gradient(135deg, #ef4444, #fca5a5);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .error-icon  { font-size: 3rem; margin-bottom: .5rem; animation: pulse 2s ease-in-out infinite; }
    .error-title { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 .5rem; }
    .error-msg   { color: rgba(255,255,255,.6); font-size: .95rem; margin-bottom: 2rem; max-width: 400px; margin-inline: auto; line-height: 1.6; }
    .btn-group   { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
    .btn {
      display: inline-flex; align-items: center; gap: .4rem;
      padding: .7rem 1.6rem; border-radius: 50px; font-weight: 600;
      font-size: .9rem; text-decoration: none; border: 2px solid transparent;
      transition: transform .2s, box-shadow .2s;
    }
    .btn:hover   { transform: translateY(-2px); }
    .btn-danger  { background: #dc2626; color: #fff; }
    .btn-danger:hover { background: #b91c1c; box-shadow: 0 6px 20px rgba(220,38,38,.4); }
    .btn-outline { background: transparent; color: #fff; border-color: rgba(255,255,255,.3); }
    .btn-outline:hover { background: rgba(255,255,255,.08); }
    .school-label { margin-top: 2.5rem; font-size: .75rem; color: rgba(255,255,255,.25); }
  </style>
</head>
<body>
  <div class="error-wrap">
    <div class="error-icon">⚠️</div>
    <div class="error-code">500</div>
    <div class="error-title">Internal Server Error</div>
    <p class="error-msg">
      Something went wrong on our end. The issue has been logged.
      Please try again in a moment or contact the system administrator.
    </p>
    <div class="btn-group">
      <a href="<?= $appUrl ?>/landing.php" class="btn btn-danger">← Back to Home</a>
      <a href="javascript:history.back()" class="btn btn-outline">Go Back</a>
    </div>
    <div class="school-label">Don Marcelo C. Marty Elementary School — ATS</div>
  </div>
</body>
</html>
