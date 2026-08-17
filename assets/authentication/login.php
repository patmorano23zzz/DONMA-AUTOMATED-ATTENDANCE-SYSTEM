<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ?force=1 clears any existing session and shows the login form (used by kiosk Staff Login link)
if (!empty($_GET['force'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
}

if (!empty($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
$u = APP_URL; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip         = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rlKey      = 'login_' . $ip;

    // Rate limit: 5 attempts per 5 minutes per IP
    if (!rate_limit($rlKey, 5, 300)) {
        $wait  = rate_limit_retry_after($rlKey, 300);
        $error = "Too many login attempts. Please wait {$wait} seconds before trying again.";
    } else {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    if (!$identifier || !$password) { $error = 'Please fill in all fields.'; }
    else {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT id,name,role,password_hash,is_active FROM users WHERE email=? OR teacher_id=? LIMIT 1');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) { $error = 'Account pending approval. Contact the administrator.'; }
            else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];
                header('Location: ' . $u . '/index.php'); exit;
            }
        } else { $error = 'Invalid credentials. Please try again.'; }
    }
    } // end rate limit block
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | DONMA ATS</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link href="<?= $u ?>/css/style.css" rel="stylesheet">
  <script src="<?= $u ?>/js/config.js"></script>
  <script src="<?= $u ?>/js/color-modes.js"></script>
  <style>
  *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }
  :root { --primary:#1a56db; --primary-d:#1044b8; --radius:14px; }
  @keyframes fadeUp  { from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)} }
  @keyframes gradMove{ 0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%} }
  @keyframes float   { 0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)} }
  @keyframes pulse   { 0%,100%{transform:scale(1)}50%{transform:scale(1.04)} }
  @keyframes spin    { to{transform:rotate(360deg)} }

  body {
    font-family:'Segoe UI',system-ui,sans-serif;
    min-height:100vh; display:flex; flex-direction:column;
    background:linear-gradient(135deg,#0d1b2a 0%,#1a3a6b 50%,#0d1b2a 100%);
    background-size:300% 300%; animation:gradMove 10s ease infinite;
  }

  /* -- Top header bar -- */
  .page-header {
    background:rgba(255,255,255,.05);
    backdrop-filter:blur(12px);
    border-bottom:1px solid rgba(255,255,255,.1);
    padding:.85rem 2rem;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:.75rem;
    animation:fadeUp .5s ease both;
  }
  .page-header .brand { display:flex; align-items:center; gap:.75rem; color:#fff; font-weight:700; font-size:.95rem; text-decoration:none; }
  .page-header .nav   { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .page-header .nav a {
    color:rgba(255,255,255,.75); font-size:.82rem; padding:.38rem .85rem;
    border-radius:50px; border:1px solid rgba(255,255,255,.15);
    text-decoration:none; transition:all .2s;
  }
  .page-header .nav a:hover { color:#fff; background:rgba(255,255,255,.12); border-color:rgba(255,255,255,.35); }

  /* -- Main layout -- */
  .login-wrap {
    flex:1; display:flex; align-items:center; justify-content:center;
    padding:2rem 1rem;
  }
  .login-container { width:100%; max-width:420px; animation:fadeUp .6s ease .1s both; }

  /* -- School branding -- */
  .school-brand { text-align:center; margin-bottom:1.75rem; }
  .school-brand .icon-wrap {
    display:inline-flex; align-items:center; justify-content:center;
    width:88px; height:88px; border-radius:50%;
    background:transparent; margin-bottom:1rem;
    animation:float 3.5s ease-in-out infinite;
    overflow:visible;
  }
  .school-brand h1 { color:#fff; font-size:1.05rem; font-weight:700; line-height:1.35; }
  .school-brand p  { color:rgba(255,255,255,.55); font-size:.8rem; margin-top:.3rem; }

  /* -- Card -- */
  .login-card {
    background:rgba(255,255,255,.07);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.15);
    border-radius:var(--radius);
    padding:2.25rem;
    box-shadow:0 24px 64px rgba(0,0,0,.35);
  }
  .card-title { color:#fff; font-size:1rem; font-weight:700; text-align:center; margin-bottom:1.5rem; }

  /* -- Form -- */
  .form-group { margin-bottom:1.1rem; }
  .form-group label { display:block; color:rgba(255,255,255,.8); font-size:.82rem; font-weight:600; margin-bottom:.45rem; }
  .form-group input {
    width:100%; background:rgba(255,255,255,.09); border:1.5px solid rgba(255,255,255,.18);
    border-radius:9px; padding:.7rem 1rem; color:#fff; font-size:.9rem;
    outline:none; transition:border-color .25s, background .25s;
  }
  .form-group input::placeholder { color:rgba(255,255,255,.35); }
  .form-group input:focus { border-color:rgba(99,179,237,.7); background:rgba(255,255,255,.13); }
  .form-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:.45rem; }
  .form-row a { color:#93c5fd; font-size:.78rem; text-decoration:none; }
  .form-row a:hover { text-decoration:underline; }

  /* -- Alert -- */
  .alert { padding:.7rem 1rem; border-radius:9px; font-size:.85rem; margin-bottom:1rem; }
  .alert-danger { background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.3); color:#fca5a5; }

  /* -- Submit btn -- */
  .btn-submit {
    width:100%; padding:.8rem; border-radius:50px; border:none; cursor:pointer;
    font-size:.95rem; font-weight:700; color:#fff;
    background:linear-gradient(135deg,var(--primary),#3b82f6);
    background-size:200% 200%; transition:transform .2s, box-shadow .2s, background-position .4s;
    margin-top:.5rem; letter-spacing:.01em;
  }
  .btn-submit:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(26,86,219,.5); background-position:right center; }
  .btn-submit:active { transform:translateY(0); }
  .btn-submit.loading { pointer-events:none; opacity:.75; }
  .btn-submit.loading::after { content:''; display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; margin-left:.6rem; vertical-align:middle; }

  /* -- Divider & bottom links -- */
  .divider { display:flex; align-items:center; gap:.75rem; margin:1.25rem 0; }
  .divider::before,.divider::after { content:''; flex:1; height:1px; background:rgba(255,255,255,.12); }
  .divider span { color:rgba(255,255,255,.35); font-size:.78rem; white-space:nowrap; }
  .quick-links { display:flex; gap:.75rem; }
  .quick-links a {
    flex:1; text-align:center; padding:.6rem; border-radius:9px;
    border:1px solid rgba(255,255,255,.15); color:rgba(255,255,255,.75);
    font-size:.82rem; text-decoration:none; transition:all .2s;
  }
  .quick-links a:hover { background:rgba(255,255,255,.1); color:#fff; }
  .bottom-link { text-align:center; margin-top:1.25rem; color:rgba(255,255,255,.5); font-size:.82rem; }
  .bottom-link a { color:#93c5fd; text-decoration:none; }
  .bottom-link a:hover { text-decoration:underline; }

  /* -- Checkbox -- */
  .form-check { display:flex; align-items:center; gap:.5rem; }
  .form-check input { width:16px; height:16px; accent-color:var(--primary); flex-shrink:0; }
  .form-check label { color:rgba(255,255,255,.7); font-size:.82rem; cursor:pointer; }

  /* -- Responsive -- */
  @media(max-width:480px){
    .login-card { padding:1.25rem; }
    .page-header { padding:.6rem .75rem; }
    .page-header .brand { font-size:.78rem; }
    .page-header .nav a { padding:.3rem .6rem; font-size:.72rem; }
    .login-wrap { padding:1rem .75rem; }
    .school-brand .icon-wrap { width:70px; height:70px; }
    .school-brand .icon-wrap img { width:70px; height:70px; }
    .school-brand h1 { font-size:.92rem; }
    .school-brand p  { font-size:.73rem; }
    .card-title { font-size:.9rem; margin-bottom:1rem; }
    .form-group { margin-bottom:.8rem; }
    .form-group input { padding:.6rem .85rem; font-size:.85rem; }
    .btn-submit { padding:.7rem; font-size:.88rem; }
    .bottom-link { font-size:.75rem; margin-top:.85rem; }
  }
  </style>
</head>
<body>

<!-- Header -->
<header class="page-header">
  <a href="<?= $u ?>/landing.php" class="brand">
    <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.25);">
    Don Marcelo C. Marty Elementary School — ATS
  </a>
  <nav class="nav">
    <a href="<?= $u ?>/landing.php">← Home</a>
    <a href="<?= $u ?>/kiosk.php">Student Kiosk</a>
    <a href="<?= $u ?>/students/register.php">Student Registration</a>
  </nav>
</header>

<!-- Login form -->
<div class="login-wrap">
  <div class="login-container">
    <div class="school-brand">
      <div class="icon-wrap">
        <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" style="width:88px;height:88px;object-fit:contain;">
      </div>
      <h1>Don Marcelo C. Marty Elementary School</h1>
      <p>Automated Attendance Tracking System</p>
    </div>

    <div class="login-card">
      <div class="card-title">Staff Sign In</div>

      <?php if (!empty($_GET['timeout'])): ?>
      <div class="alert alert-danger">⏱ Your session expired due to inactivity. Please sign in again.</div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= $u ?>/authentication/login.php" id="login-form" novalidate>
        <div class="form-group">
          <label for="identifier">Email or Teacher ID</label>
          <input id="identifier" name="identifier" type="text"
            placeholder="admin@donma.edu  or  T-0001"
            value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
            autocomplete="username" required>
        </div>
        <div class="form-group">
          <div class="form-row">
            <label for="password">Password</label>
            <a href="<?= $u ?>/authentication/reset-password.php">Forgot password?</a>
          </div>
          <input id="password" name="password" type="password"
            placeholder="Your password" autocomplete="current-password" required>
        </div>
        <div class="form-check" style="margin-bottom:1rem;">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember">Remember me on this device</label>
        </div>
        <button class="btn-submit" type="submit" id="btn-submit">Sign In</button>
      </form>

      <div class="divider"><span>or quick access</span></div>
      <div class="quick-links">
        <a href="<?= $u ?>/kiosk.php">📷 Student Kiosk</a>
        <a href="<?= $u ?>/students/register.php">📝 Register Student</a>
      </div>
    </div>

    <div class="bottom-link">
      New teacher? <a href="<?= $u ?>/authentication/register.php">Request an account →</a>
    </div>
  </div>
</div>

<script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
<script>
  document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('btn-submit');
    btn.classList.add('loading');
    btn.textContent = 'Signing in';
  });
</script>
</body>
</html>
