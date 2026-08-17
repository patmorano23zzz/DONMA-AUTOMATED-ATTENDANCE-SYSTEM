<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
$u = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Kiosk | DONMA ATS</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link href="<?= $u ?>/css/style.css" rel="stylesheet">
  <style>
  *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
  :root { --primary:#1a6b3a; --primary-d:#145530; --gold:#c9a227; --success:#1a6b3a; --warning:#c9a227; --danger:#dc2626; }
  @keyframes fadeUp  { from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)} }
  @keyframes gradMove{ 0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%} }
  @keyframes pulse   { 0%,100%{transform:scale(1)}50%{transform:scale(1.06)} }
  @keyframes spin    { to{transform:rotate(360deg)} }
  @keyframes bounce  { 0%,100%{transform:translateY(0)}40%{transform:translateY(-6px)} }
  @keyframes slideIn { from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)} }
  @keyframes ripple  { to{transform:scale(4);opacity:0} }

  body {
    font-family:'Segoe UI',system-ui,sans-serif;
    background:linear-gradient(135deg,#050f07 0%,#0a1f0f 40%,#0d3320 100%);
    background-size:300% 300%; animation:gradMove 12s ease infinite;
    color:#fff; min-height:100vh; display:flex; flex-direction:column;
  }

  /* ── Kiosk Header ── */
  .kiosk-header {
    background:rgba(255,255,255,.04);
    backdrop-filter:blur(12px);
    border-bottom:1px solid rgba(255,255,255,.08);
    padding:.85rem 1.5rem;
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem;
    animation:fadeUp .45s ease both;
    flex-shrink:0;
  }
  .kiosk-header .brand { display:flex; align-items:center; gap:.65rem; font-weight:700; font-size:.9rem; color:#fff; text-decoration:none; }
  .kiosk-header .brand span { color:#93c5fd; font-weight:400; }
  .kiosk-header .header-right { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
  .live-badge {
    display:flex; align-items:center; gap:.4rem; font-size:.75rem;
    color:rgba(255,255,255,.65); background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.1); border-radius:50px; padding:.3rem .85rem;
  }
  .live-dot { width:7px;height:7px;border-radius:50%;background:#22c55e;animation:pulse 1.5s infinite; }
  .header-links { display:flex; gap:.4rem; flex-wrap:wrap; }
  .header-links a {
    color:rgba(255,255,255,.65); font-size:.78rem; padding:.35rem .8rem;
    border-radius:50px; border:1px solid rgba(255,255,255,.12); text-decoration:none;
    transition:all .2s;
  }
  .header-links a:hover { color:#fff; background:rgba(255,255,255,.1); border-color:rgba(255,255,255,.3); }
  #header-clock { font-size:.8rem; color:rgba(255,255,255,.55); font-variant-numeric:tabular-nums; }

  /* ── Kiosk body ── */
  .kiosk-body {
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center; padding:1.5rem 1rem;
  }
  .kiosk-school { text-align:center; margin-bottom:1.5rem; animation:fadeUp .5s ease .1s both; }
  .kiosk-school h2 { font-size:clamp(.9rem,2.5vw,1.15rem); font-weight:700; opacity:.9; }
  .kiosk-school p  { font-size:.75rem; opacity:.5; margin-top:.2rem; }

  /* ── Screen card ── */
  .kiosk-card {
    background:rgba(255,255,255,.06);
    backdrop-filter:blur(18px);
    -webkit-backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,.12);
    border-radius:20px; padding:2rem 1.75rem;
    width:100%; max-width:460px;
    text-align:center; animation:fadeUp .55s ease .15s both;
    box-shadow:0 24px 64px rgba(0,0,0,.4);
    position:relative; overflow:hidden;
  }
  .kiosk-card h2 { font-size:1.2rem; font-weight:700; margin-bottom:.35rem; }
  .kiosk-card p.sub { font-size:.82rem; color:rgba(255,255,255,.5); margin-bottom:1.25rem; }

  /* ── QR scanner ── */
  #qr-reader { width:100%; border-radius:12px; overflow:hidden; }
  #scan-status { font-size:.8rem; color:rgba(255,255,255,.5); margin-top:.75rem; }
  #live-time { font-size:2.2rem; font-weight:800; letter-spacing:.08em; color:#fff; font-variant-numeric:tabular-nums; margin-top:.75rem; }

  /* ── Face screen ── */
  #face-video { width:100%; border-radius:12px; background:#000; margin-bottom:.75rem; }
  #face-status { font-size:.85rem; color:rgba(255,255,255,.65); }

  /* ── Buttons ── */
  .btn {
    display:inline-flex; align-items:center; gap:.45rem;
    padding:.65rem 1.5rem; border-radius:50px; font-size:.875rem; font-weight:600;
    cursor:pointer; border:1.5px solid transparent; text-decoration:none;
    transition:transform .2s, box-shadow .2s, background .2s; position:relative; overflow:hidden;
  }
  .btn:hover { transform:translateY(-2px); }
  .btn:active { transform:translateY(0); }
  .btn-primary { background:var(--primary); color:#fff; }
  .btn-primary:hover { box-shadow:0 6px 20px rgba(26,107,58,.45); }
  .btn-primary:hover { box-shadow:0 6px 20px rgba(26,86,219,.45); }
  .btn-light { background:rgba(255,255,255,.12); color:#fff; border-color:rgba(255,255,255,.2); }
  .btn-light:hover { background:rgba(255,255,255,.2); }
  .btn-danger { background:rgba(220,38,38,.2); color:#fca5a5; border-color:rgba(220,38,38,.3); }
  .btn-danger:hover { background:rgba(220,38,38,.35); }
  .btn-group { display:flex; gap:.6rem; justify-content:center; flex-wrap:wrap; margin-top:1rem; }

  /* ── Result screen ── */
  .result-icon { font-size:3.5rem; margin-bottom:.5rem; animation:bounce .6s ease both; }
  .result-title { font-size:1.3rem; font-weight:800; margin-bottom:.25rem; }
  .result-name  { font-size:1.1rem; font-weight:600; color:#93c5fd; margin-bottom:.25rem; }
  .result-detail{ font-size:.82rem; color:rgba(255,255,255,.55); }
  #auto-reset-bar { height:4px; background:var(--primary); border-radius:2px; width:100%; margin-top:1rem; transition:width 6s linear; }

  /* ── Error screen ── */
  .error-icon { font-size:3rem; margin-bottom:.5rem; }
  .error-title { font-size:1.2rem; font-weight:700; margin-bottom:.35rem; color:#fca5a5; }
  .error-msg   { font-size:.85rem; color:rgba(255,255,255,.5); margin-bottom:1rem; }

  /* ── Footer ── */
  .kiosk-footer {
    text-align:center; padding:.75rem; font-size:.72rem; color:rgba(255,255,255,.25);
    border-top:1px solid rgba(255,255,255,.06);
  }

  /* ── Responsive ── */
  @media(max-width:480px){
    .kiosk-card { padding:1.25rem 1rem; border-radius:14px; }
    .kiosk-header { padding:.6rem .75rem; }
    .header-links { display:none; }
    .kiosk-body { padding:1rem .75rem; }
    .kiosk-card h2 { font-size:1rem; }
    .kiosk-card p.sub { font-size:.75rem; margin-bottom:.85rem; }
    .kiosk-school h2 { font-size:.9rem; }
    .kiosk-school p  { font-size:.68rem; }
    #live-time { font-size:1.6rem; }
    .result-icon { font-size:2.5rem; }
    .result-title { font-size:1.1rem; }
    .result-name  { font-size:.95rem; }
    .btn { padding:.55rem 1.1rem; font-size:.82rem; }
  }
  </style>
</head>
<body>

<!-- ── Kiosk Header ─────────────────────────────────────────────────────── -->
<header class="kiosk-header">
  <a href="<?= $u ?>/landing.php" class="brand">
    <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.25);">
    DONMA <span>ATS</span>
  </a>
  <div class="header-right">
    <div class="live-badge"><span class="live-dot"></span>Kiosk Live</div>
    <div id="header-clock"></div>
    <nav class="header-links">
      <a href="<?= $u ?>/students/register.php">📝 Register</a>
      <a href="<?= $u ?>/authentication/login.php?force=1">Staff Login</a>
    </nav>
  </div>
</header>

<!-- ── Kiosk Body ──────────────────────────────────────────────────────── -->
<div class="kiosk-body">
  <div class="kiosk-school">
    <h2>Don Marcelo C. Marty Elementary School</h2>
    <p>Student Attendance Kiosk — No login required</p>
  </div>

  <!-- SCREEN: Scan QR -->
  <div class="kiosk-card" id="screen-scan">
    <h2>Scan Your QR Code</h2>
    <p class="sub">Hold your QR code in front of the camera</p>
    <div id="qr-reader"></div>
    <p id="scan-status">Initializing camera…</p>
    <div id="live-time"></div>
  </div>

  <!-- SCREEN: Face Verification -->
  <div class="kiosk-card d-none" id="screen-face">
    <h2>Face Verification</h2>
    <p class="sub">Look straight at the camera and hold still</p>
    <video id="face-video" autoplay playsinline muted></video>
    <canvas id="face-canvas" style="display:none;"></canvas>
    <p id="face-status">Starting camera…</p>
    <div class="btn-group">
      <button class="btn btn-light" id="btn-override">Skip / Override Face</button>
      <button class="btn btn-danger" id="btn-cancel-face">Cancel</button>
    </div>
  </div>

  <!-- SCREEN: Result -->
  <div class="kiosk-card d-none" id="screen-result">
    <div class="result-icon" id="result-icon">✅</div>
    <div class="result-title" id="result-title">Attendance Recorded</div>
    <div class="result-name"  id="result-name"></div>
    <div class="result-detail" id="result-detail"></div>
    <div id="auto-reset-bar"></div>
    <p style="font-size:.75rem;color:rgba(255,255,255,.35);margin-top:.5rem;">Auto-reset in 6 seconds…</p>
    <div class="btn-group">
      <button class="btn btn-light" id="btn-reset">Scan Another</button>
    </div>
  </div>

  <!-- SCREEN: Error -->
  <div class="kiosk-card d-none" id="screen-error">
    <div class="error-icon">❌</div>
    <div class="error-title" id="error-title">Error</div>
    <p class="error-msg" id="error-msg"></p>
    <div class="btn-group">
      <button class="btn btn-primary" id="btn-error-reset">Try Again</button>
      <a href="<?= $u ?>/students/register.php" class="btn btn-light">Register Student</a>
    </div>
  </div>
</div>

<footer class="kiosk-footer">
  &copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — Attendance Tracking System
  &nbsp;|&nbsp; <a href="<?= $u ?>/authentication/login.php?force=1" style="color:rgba(255,255,255,.3);">Staff Login</a>
</footer>

<script src="<?= $u ?>/vendors/html5-qrcode/html5-qrcode.min.js"></script>
<script>
const APP_URL = <?= json_encode($u) ?>;

// Live clock
const clockEls = [document.getElementById('header-clock'), document.getElementById('live-time')];
function tick() {
  const now = new Date();
  clockEls[0].textContent = now.toLocaleString('en-PH',{weekday:'short',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
  clockEls[1].textContent = now.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
tick(); setInterval(tick, 1000);

// Screens
const screens = ['scan','face','result','error'];
function showScreen(name) {
  screens.forEach(s => document.getElementById('screen-'+s).classList.toggle('d-none', s !== name));
}

// QR Scanner
let scanner;
function startScanner() {
  document.getElementById('scan-status').textContent = 'Initializing camera…';
  scanner = new Html5Qrcode('qr-reader');
  scanner.start({facingMode:'environment'},{fps:10,qrbox:{width:240,height:240}},onQrSuccess,()=>{})
    .then(()=>{ document.getElementById('scan-status').textContent = 'Ready — hold your QR code up to the camera'; })
    .catch(err=>{ document.getElementById('scan-status').textContent = 'Camera error: '+err; });
}

async function onQrSuccess(qrText) {
  if (!qrText.startsWith('DCMMES-')) {
    await stopScanner(); showError('Invalid QR Code','This QR code is not recognised. Please use your DONMA QR card.'); return;
  }
  const lrn = qrText.replace('DCMMES-','');
  await stopScanner();
  const res  = await fetch(APP_URL+'/api/attendance/check.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({lrn})});
  const data = await res.json();
  if (!data.valid) { showError('Student Not Found', data.message||'LRN not registered.'); return; }
  if (data.fully_recorded) { showResult('✅','Already Recorded Today',data.name,`Time In: ${data.time_in}  |  Time Out: ${data.time_out}`); return; }
  currentLRN = lrn; currentData = data;
  startFaceVerification();
}

async function stopScanner() {
  if (scanner) { try { await scanner.stop(); } catch(e){} scanner = null; }
}

// Face verification
let currentLRN=null, currentData=null, faceStream=null;
async function startFaceVerification() {
  showScreen('face');
  document.getElementById('face-status').textContent = 'Starting camera…';
  try {
    faceStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'}});
    document.getElementById('face-video').srcObject = faceStream;
    document.getElementById('face-status').textContent = 'Hold still — verifying…';
    setTimeout(captureAndVerify, 2000);
  } catch(e) { document.getElementById('face-status').textContent = 'Camera unavailable. Use Override.'; }
}

async function captureAndVerify() {
  const video=document.getElementById('face-video'), canvas=document.getElementById('face-canvas');
  canvas.width=video.videoWidth||320; canvas.height=video.videoHeight||240;
  canvas.getContext('2d').drawImage(video,0,0);
  const imageData = canvas.toDataURL('image/jpeg',.8);
  stopFaceCamera();
  document.getElementById('face-status').textContent = 'Submitting…';
  await recordAttendance(currentLRN, imageData);
}

function stopFaceCamera() {
  if (faceStream){faceStream.getTracks().forEach(t=>t.stop());faceStream=null;}
}

document.getElementById('btn-override').addEventListener('click',async()=>{ stopFaceCamera(); await recordAttendance(currentLRN,null); });
document.getElementById('btn-cancel-face').addEventListener('click',()=>{ stopFaceCamera(); resetKiosk(); });

async function recordAttendance(lrn, imageData) {
  const res  = await fetch(APP_URL+'/api/attendance/record.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({lrn,face_image:imageData})});
  const data = await res.json();
  if (data.success) {
    const icon  = data.action==='timeout' ? '👋' : (data.status==='late' ? '⏰' : '✅');
    const title = data.action==='timeout' ? 'Time Out Recorded' : `Time In — ${(data.status||'').toUpperCase()}`;
    const detail= data.action==='timeout' ? `Time Out: ${data.time_out}` : `Time In: ${data.time_in}`;
    showResult(icon, title, data.name, detail);
  } else { showError('Failed', data.message||'Could not record attendance.'); }
}

function showResult(icon, title, name, detail) {
  showScreen('result');
  document.getElementById('result-icon').textContent   = icon;
  document.getElementById('result-title').textContent  = title;
  document.getElementById('result-name').textContent   = name;
  document.getElementById('result-detail').textContent = detail;
  const bar = document.getElementById('auto-reset-bar');
  bar.style.width = '100%';
  setTimeout(()=>{ bar.style.width='0%'; }, 50);
  setTimeout(resetKiosk, 6000);
}

function showError(title, msg) {
  showScreen('error');
  document.getElementById('error-title').textContent = title;
  document.getElementById('error-msg').textContent   = msg;
}

function resetKiosk() {
  currentLRN=null; currentData=null; stopFaceCamera();
  showScreen('scan'); startScanner();
}

document.getElementById('btn-reset').addEventListener('click', resetKiosk);
document.getElementById('btn-error-reset').addEventListener('click', resetKiosk);

startScanner();
</script>
</body>
</html>
