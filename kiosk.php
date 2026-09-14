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
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png">
  <link href="<?= $u ?>/css/style.css" rel="stylesheet">
  <style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{--primary:#1a6b3a;--primary-d:#145530;--gold:#c9a227}
  @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
  @keyframes gradMove{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}
  @keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}
  @keyframes bounce{0%,100%{transform:translateY(0)}40%{transform:translateY(-6px)}}
  @keyframes slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
  body{font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg,#050f07 0%,#0a1f0f 40%,#0d3320 100%);background-size:300% 300%;animation:gradMove 12s ease infinite;color:#fff;min-height:100vh;display:flex;flex-direction:column}
  .kiosk-header{background:rgba(255,255,255,.04);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,.08);padding:.85rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;animation:fadeUp .45s ease both;flex-shrink:0}
  .kiosk-header .brand{display:flex;align-items:center;gap:.65rem;font-weight:700;font-size:.9rem;color:#fff;text-decoration:none}
  .kiosk-header .brand span{color:#93c5fd;font-weight:400}
  .header-right{display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
  .live-badge{display:flex;align-items:center;gap:.4rem;font-size:.75rem;color:rgba(255,255,255,.65);background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:50px;padding:.3rem .85rem}
  .live-dot{width:7px;height:7px;border-radius:50%;background:#22c55e;animation:pulse 1.5s infinite}
  .header-links{display:flex;gap:.4rem;flex-wrap:wrap}
  .header-links a{color:rgba(255,255,255,.65);font-size:.78rem;padding:.35rem .8rem;border-radius:50px;border:1px solid rgba(255,255,255,.12);text-decoration:none;transition:all .2s}
  .header-links a:hover{color:#fff;background:rgba(255,255,255,.1)}
  #header-clock{font-size:.8rem;color:rgba(255,255,255,.55);font-variant-numeric:tabular-nums}
  .kiosk-body{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.5rem 1rem}
  .kiosk-school{text-align:center;margin-bottom:1.5rem;animation:fadeUp .5s ease .1s both}
  .kiosk-school h2{font-size:clamp(.9rem,2.5vw,1.15rem);font-weight:700;opacity:.9}
  .kiosk-school p{font-size:.75rem;opacity:.5;margin-top:.2rem}
  .kiosk-card{background:rgba(255,255,255,.06);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:2rem 1.75rem;width:100%;max-width:480px;text-align:center;animation:fadeUp .55s ease .15s both;box-shadow:0 24px 64px rgba(0,0,0,.4);position:relative;overflow:hidden}
  .kiosk-card h2{font-size:1.2rem;font-weight:700;margin-bottom:.35rem}
  .kiosk-card p.sub{font-size:.82rem;color:rgba(255,255,255,.5);margin-bottom:1.25rem}
  #qr-reader{width:100%;border-radius:12px;overflow:hidden}
  #scan-status{font-size:.8rem;color:rgba(255,255,255,.5);margin-top:.75rem}
  #live-time{font-size:2.2rem;font-weight:800;letter-spacing:.08em;color:#fff;font-variant-numeric:tabular-nums;margin-top:.75rem}
  /* Face capture */
  .face-wrap{position:relative;width:100%;border-radius:14px;overflow:hidden;background:#000;margin-bottom:.75rem}
  #face-video{width:100%;display:block;border-radius:14px}
  .face-overlay{position:absolute;inset:0;pointer-events:none}
  .face-oval{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:54%;padding-top:68%;border-radius:50%;border:3px solid rgba(255,255,255,.35);transition:border-color .3s}
  .face-oval.good{border-color:#22c55e;box-shadow:0 0 0 4px rgba(34,197,94,.2)}
  .face-oval.bad{border-color:#ef4444;box-shadow:0 0 0 4px rgba(239,68,68,.2)}
  .face-guide{position:absolute;bottom:10px;left:0;right:0;text-align:center;font-size:.75rem;color:rgba(255,255,255,.8);background:rgba(0,0,0,.45);padding:.3rem .5rem}
  #face-status{font-size:.85rem;color:rgba(255,255,255,.75);margin-bottom:.5rem;min-height:1.4em}
  .face-checklist{display:flex;flex-wrap:wrap;gap:.4rem;justify-content:center;margin-bottom:.75rem;font-size:.75rem}
  .face-checklist span{padding:.2rem .6rem;border-radius:50px;background:rgba(255,255,255,.08);color:rgba(255,255,255,.5);transition:all .3s}
  .face-checklist span.ok{background:rgba(34,197,94,.15);color:#86efac}
  .face-checklist span.fail{background:rgba(239,68,68,.15);color:#fca5a5}
  .countdown-ring{display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:50%;border:3px solid #22c55e;font-size:1.3rem;font-weight:800;color:#22c55e;margin:.5rem auto;display:none}
  /* Buttons */
  .btn{display:inline-flex;align-items:center;gap:.45rem;padding:.65rem 1.5rem;border-radius:50px;font-size:.875rem;font-weight:600;cursor:pointer;border:1.5px solid transparent;text-decoration:none;transition:transform .2s,box-shadow .2s,background .2s;position:relative;overflow:hidden}
  .btn:hover{transform:translateY(-2px)}
  .btn-primary{background:var(--primary);color:#fff}
  .btn-primary:hover{box-shadow:0 6px 20px rgba(26,107,58,.45)}
  .btn-light{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.2)}
  .btn-light:hover{background:rgba(255,255,255,.2)}
  .btn-danger{background:rgba(220,38,38,.2);color:#fca5a5;border-color:rgba(220,38,38,.3)}
  .btn-danger:hover{background:rgba(220,38,38,.35)}
  .btn-group{display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap;margin-top:1rem}
  /* Result */
  .result-icon{font-size:3.5rem;margin-bottom:.5rem;animation:bounce .6s ease both}
  .result-title{font-size:1.3rem;font-weight:800;margin-bottom:.25rem}
  .result-name{font-size:1.1rem;font-weight:600;color:#93c5fd;margin-bottom:.25rem}
  .result-detail{font-size:.82rem;color:rgba(255,255,255,.55)}
  #auto-reset-bar{height:4px;background:var(--primary);border-radius:2px;width:100%;margin-top:1rem;transition:width 6s linear}
  /* Error */
  .error-icon{font-size:3rem;margin-bottom:.5rem}
  .error-title{font-size:1.2rem;font-weight:700;margin-bottom:.35rem;color:#fca5a5}
  .error-msg{font-size:.85rem;color:rgba(255,255,255,.5);margin-bottom:1rem}
  /* Kiosk message banner */
  #kiosk-msg-banner{position:fixed;top:0;left:0;right:0;z-index:9999;padding:.85rem 1.5rem;font-size:.95rem;font-weight:600;text-align:center;animation:slideDown .4s ease;display:none;cursor:pointer}
  #kiosk-msg-banner.info{background:rgba(59,130,246,.9);color:#fff}
  #kiosk-msg-banner.warning{background:rgba(234,179,8,.95);color:#1a2e1a}
  #kiosk-msg-banner.success{background:rgba(34,197,94,.9);color:#fff}
  .kiosk-footer{text-align:center;padding:.75rem;font-size:.72rem;color:rgba(255,255,255,.25);border-top:1px solid rgba(255,255,255,.06)}
  @media(max-width:480px){
    .kiosk-card{padding:1.25rem 1rem;border-radius:14px}
    .kiosk-header{padding:.6rem .75rem}
    .header-links{display:none}
    #live-time{font-size:1.6rem}
    .result-icon{font-size:2.5rem}
    .result-title{font-size:1.1rem}
    .result-name{font-size:.95rem}
    .btn{padding:.55rem 1.1rem;font-size:.82rem}
  }
  </style>
</head>
<body>

<div id="kiosk-msg-banner" onclick="this.style.display='none'"></div>

<header class="kiosk-header">
  <a href="<?= $u ?>/landing.php" class="brand">
    <img src="<?= $u ?>/assets/img/school-logo.png" alt="Logo" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.25);">
    DONMA <span>ATS</span>
  </a>
  <div class="header-right">
    <div class="live-badge"><span class="live-dot"></span>Kiosk Live</div>
    <div id="header-clock"></div>
    <nav class="header-links">
      <a href="<?= $u ?>/authentication/login.php?force=1">Staff Login</a>
    </nav>
  </div>
</header>

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

  <!-- SCREEN: Face Capture -->
  <div class="kiosk-card d-none" id="screen-face">
    <h2>📸 Face Capture</h2>
    <p class="sub">Position your face inside the oval and hold still</p>
    <div class="face-wrap">
      <video id="face-video" autoplay playsinline muted></video>
      <canvas id="face-canvas" style="display:none"></canvas>
      <div class="face-overlay">
        <div class="face-oval" id="face-oval"></div>
        <div class="face-guide" id="face-guide">Align your face inside the oval</div>
      </div>
    </div>
    <p id="face-status">Starting camera…</p>
    <div class="face-checklist">
      <span id="chk-face" class="fail">👤 Face detected</span>
      <span id="chk-center" class="fail">🎯 Centered</span>
      <span id="chk-size" class="fail">📐 Proper size</span>
      <span id="chk-still" class="fail">🧍 Hold still</span>
    </div>
    <div class="countdown-ring" id="countdown-ring">3</div>
    <div class="btn-group">
      <button class="btn btn-danger" id="btn-cancel-face">Cancel</button>
    </div>
  </div>

  <!-- SCREEN: Result -->
  <div class="kiosk-card d-none" id="screen-result">
    <div class="result-icon" id="result-icon">✅</div>
    <div class="result-title" id="result-title">Attendance Recorded</div>
    <div class="result-name" id="result-name"></div>
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

// Clock
const clockEls = [document.getElementById('header-clock'), document.getElementById('live-time')];
function tick() {
  const now = new Date();
  clockEls[0].textContent = now.toLocaleString('en-PH',{weekday:'short',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
  if (clockEls[1]) clockEls[1].textContent = now.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
tick(); setInterval(tick, 1000);

// Screens
function showScreen(name) {
  ['scan','face','result','error'].forEach(s =>
    document.getElementById('screen-'+s).classList.toggle('d-none', s !== name)
  );
}

// ── QR Scanner ──────────────────────────────────────────────────────────────
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
    await stopScanner(); showError('Invalid QR Code','This QR code is not recognised.'); return;
  }
  const lrn = qrText.replace('DCMMES-','');
  // Grab stream BEFORE stopping scanner so we can reuse it for face capture
  const qrVideo = document.querySelector('#qr-reader video');
  const savedStream = (qrVideo && qrVideo.srcObject) ? qrVideo.srcObject : null;
  await stopScanner();
  const res  = await fetch(APP_URL+'/api/attendance/check.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({lrn})});
  const data = await res.json();
  if (!data.valid) { showError('Student Not Found', data.message||'LRN not registered.'); return; }
  if (data.fully_recorded) { showResult('✅','Already Recorded Today',data.name,`Time In: ${data.time_in}  |  Time Out: ${data.time_out}`); return; }
  currentLRN = lrn; currentData = data;
  startFaceCapture(savedStream);
}

async function stopScanner() {
  if (scanner) { try { await scanner.stop(); } catch(e){} scanner = null; }
}

// ── Smart Face Capture ───────────────────────────────────────────────────────
let currentLRN = null, currentData = null, faceStream = null;
let faceCheckInterval = null, countdownTimer = null, countdownVal = 3;
let stillFrames = 0, prevFrameData = null;

async function startFaceCapture(existingStream) {
  showScreen('face');
  resetChecks();
  document.getElementById('face-status').textContent = 'Starting camera…';
  document.getElementById('countdown-ring').style.display = 'none';
  try {
    // Reuse QR scanner stream if available (avoids camera conflict)
    if (existingStream && existingStream.getTracks().some(t => t.readyState === 'live')) {
      faceStream = existingStream;
    } else {
      faceStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'environment',width:{ideal:640},height:{ideal:480}}});
    }
    const video = document.getElementById('face-video');
    video.srcObject = faceStream;
    await new Promise(r => { video.onloadedmetadata = r; });
    video.play();
    document.getElementById('face-status').textContent = 'Look straight at the camera';
    faceCheckInterval = setInterval(checkFaceConditions, 300);
  } catch(e) {
    document.getElementById('face-status').textContent = 'Camera unavailable — please see a teacher.';
  }
}

function resetChecks() {
  ['chk-face','chk-center','chk-size','chk-still'].forEach(id => {
    document.getElementById(id).className = 'fail';
  });
  stillFrames = 0; prevFrameData = null;
  clearCountdown();
}

function setCheck(id, ok) {
  document.getElementById(id).className = ok ? 'ok' : 'fail';
}

// Face condition checks using brightness + edge density (works for all skin tones/glasses)
function checkFaceConditions() {
  const video  = document.getElementById('face-video');
  const canvas = document.getElementById('face-canvas');
  if (!video.videoWidth) return;

  canvas.width  = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(video, 0, 0);

  const vw = video.videoWidth, vh = video.videoHeight;

  // Sample the full oval region
  const ox = Math.floor(vw * 0.20), oy = Math.floor(vh * 0.10);
  const ow = Math.floor(vw * 0.60), oh = Math.floor(vh * 0.80);
  const fullData = ctx.getImageData(ox, oy, ow, oh).data;
  const totalPx  = fullData.length / 4;

  // Compute average brightness
  let sumBright = 0;
  for (let i = 0; i < fullData.length; i += 4) {
    sumBright += (fullData[i] * 299 + fullData[i+1] * 587 + fullData[i+2] * 114) / 1000;
  }
  const avgBright = sumBright / totalPx;

  // Check 1: Face present — measure edge density in oval (faces have many edges)
  // Downsample to 40x40 for speed
  const tmpC = document.createElement('canvas'); tmpC.width = 40; tmpC.height = 40;
  const tmpX = tmpC.getContext('2d');
  tmpX.drawImage(video, ox, oy, ow, oh, 0, 0, 40, 40);
  const small = tmpX.getImageData(0, 0, 40, 40).data;
  let edges = 0;
  for (let y = 1; y < 39; y++) {
    for (let x = 1; x < 39; x++) {
      const i = (y * 40 + x) * 4;
      const iL = (y * 40 + (x-1)) * 4;
      const iU = ((y-1) * 40 + x) * 4;
      const gx = Math.abs(small[i] - small[iL]);
      const gy = Math.abs(small[i] - small[iU]);
      if (gx + gy > 30) edges++;
    }
  }
  const edgeDensity = edges / (38 * 38);
  const faceDetected = edgeDensity > 0.12 && avgBright > 30 && avgBright < 230;
  setCheck('chk-face', faceDetected);

  // Check 2: Centered — pass as long as face is detected inside the oval
  const centered = faceDetected;
  setCheck('chk-center', centered);

  // Check 3: Proper size — edge density not too low (too far) or too high (too close)
  const properSize = faceDetected && edgeDensity > 0.12 && edgeDensity < 0.55;
  setCheck('chk-size', properSize);

  // Check 4: Still — compare current frame pixels to previous frame
  let still = false;
  if (prevFrameData) {
    let diff = 0;
    const step = 8; // sample every 8th pixel for speed
    let count = 0;
    for (let i = 0; i < fullData.length; i += 4 * step) {
      diff += Math.abs(fullData[i] - prevFrameData[i]);
      count++;
    }
    const avgDiff = diff / count;
    still = avgDiff < 6;
    if (still) stillFrames++; else stillFrames = 0;
  }
  prevFrameData = new Uint8ClampedArray(fullData);
  setCheck('chk-still', stillFrames >= 4);

  const allGood = faceDetected && centered && properSize && stillFrames >= 4;
  document.getElementById('face-oval').className = 'face-oval ' + (allGood ? 'good' : 'bad');

  let guide = 'Align your face inside the oval';
  if (!faceDetected) guide = 'Look at the camera — face not detected';
  else if (edgeDensity >= 0.55) guide = 'Move back a little';
  else if (edgeDensity <= 0.12) guide = 'Move closer to the camera';
  else if (stillFrames < 4) guide = 'Hold still…';
  else guide = '✅ Perfect — hold still!';
  document.getElementById('face-guide').textContent = guide;
  document.getElementById('face-status').textContent = allGood ? 'Great! Capturing in…' : 'Follow the instructions below';

  if (allGood && countdownTimer === null) startCountdown();
  if (!allGood) clearCountdown();
}

function startCountdown() {
  countdownVal = 3;
  const ring = document.getElementById('countdown-ring');
  ring.style.display = 'inline-flex';
  ring.textContent = countdownVal;
  countdownTimer = setInterval(() => {
    countdownVal--;
    ring.textContent = countdownVal;
    if (countdownVal <= 0) { clearCountdown(); captureAndSubmit(); }
  }, 1000);
}

function clearCountdown() {
  if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
  document.getElementById('countdown-ring').style.display = 'none';
}

async function captureAndSubmit() {
  clearInterval(faceCheckInterval); faceCheckInterval = null;
  const video  = document.getElementById('face-video');
  const canvas = document.getElementById('face-canvas');
  canvas.width  = video.videoWidth  || 320;
  canvas.height = video.videoHeight || 240;
  canvas.getContext('2d').drawImage(video, 0, 0);
  const imageData = canvas.toDataURL('image/jpeg', .85);
  stopFaceCamera();
  document.getElementById('face-status').textContent = 'Submitting…';
  await recordAttendance(currentLRN, imageData);
}

function stopFaceCamera() {
  clearInterval(faceCheckInterval); faceCheckInterval = null;
  clearCountdown();
  if (faceStream) { faceStream.getTracks().forEach(t => t.stop()); faceStream = null; }
}

document.getElementById('btn-cancel-face').addEventListener('click', () => { stopFaceCamera(); resetKiosk(); });

// ── Record Attendance ────────────────────────────────────────────────────────
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
  setTimeout(() => { bar.style.width = '0%'; }, 50);
  setTimeout(resetKiosk, 6000);
}

function showError(title, msg) {
  showScreen('error');
  document.getElementById('error-title').textContent = title;
  document.getElementById('error-msg').textContent   = msg;
}

function resetKiosk() {
  currentLRN = null; currentData = null;
  stopFaceCamera();
  showScreen('scan'); startScanner();
}

document.getElementById('btn-reset').addEventListener('click', resetKiosk);
document.getElementById('btn-error-reset').addEventListener('click', resetKiosk);

startScanner();

// ── Kiosk Message Polling ────────────────────────────────────────────────────
let lastMsgId = -1;
async function pollMessages() {
  try {
    const res  = await fetch(APP_URL+'/api/messages/poll.php?since='+Math.max(0,lastMsgId));
    const data = await res.json();
    if (!data.messages) return;
    if (lastMsgId === -1) {
      // First run: snapshot current latest ID, show nothing
      lastMsgId = data.messages.length > 0 ? data.messages[data.messages.length-1].id : 0;
      return;
    }
    const fresh = data.messages.filter(m => m.id > lastMsgId);
    if (fresh.length > 0) {
      const m = fresh[fresh.length-1];
      lastMsgId = m.id;
      const banner = document.getElementById('kiosk-msg-banner');
      banner.className = m.type;
      banner.textContent = '📢 ' + m.teacher_name + ': ' + m.message + '  (tap to dismiss)';
      banner.style.display = 'block';
      setTimeout(() => { banner.style.display = 'none'; }, 15000);
    }
  } catch(e) {}
}
pollMessages();
setInterval(pollMessages, 10000);

// ── Live Monitoring ──────────────────────────────────────────────────────────
// Runs silently in background. Shares kiosk camera feed to teacher dashboard.
const SIGNAL_URL = APP_URL + '/api/monitor/signal.php';
const RTC_CONFIG = { iceServers: [
  { urls: 'stun:stun.l.google.com:19302' },
  { urls: 'stun:stun1.l.google.com:19302' }
]};

let rtcPeer = null, monitorStream = null, monitorPollTimer = null;
let lastViewerSignalId = 0, rtcReady = false, lastOfferAt = 0;

async function startMonitorBroadcast() {
  try {
    // Reuse existing QR scanner stream if available, otherwise get a new one
    // Try to get stream from the qr-reader video element first
    const qrVideo = document.querySelector('#qr-reader video');
    if (qrVideo && qrVideo.srcObject) {
      monitorStream = qrVideo.srcObject;
    } else {
      monitorStream = await navigator.mediaDevices.getUserMedia({
        video: { width:{ideal:320}, height:{ideal:240}, frameRate:{ideal:10} },
        audio: false
      });
    }
    // Attach to hidden video so stream stays alive
    let mv = document.getElementById('monitor-video');
    if (!mv) {
      mv = document.createElement('video');
      mv.id = 'monitor-video'; mv.autoplay = true; mv.muted = true;
      mv.style.cssText = 'position:fixed;width:1px;height:1px;opacity:0;pointer-events:none;top:0;left:0;';
      document.body.appendChild(mv);
    }
    mv.srcObject = monitorStream;
    monitorPollTimer = setInterval(pollViewerSignals, 3000);
    await createOffer();
  } catch(e) {
    setTimeout(startMonitorBroadcast, 30000);
  }
}

async function createOffer() {
  if (rtcPeer) { try { rtcPeer.close(); } catch(e){} }
  rtcReady = false;

  // Always try to get the freshest stream — prefer face video (during capture), then QR video
  const faceVideo = document.getElementById('face-video');
  const qrVideo   = document.querySelector('#qr-reader video');
  if (faceVideo && faceVideo.srcObject) {
    monitorStream = faceVideo.srcObject;
  } else if (qrVideo && qrVideo.srcObject) {
    monitorStream = qrVideo.srcObject;
  }
  // If still no stream or all tracks ended, bail and retry
  if (!monitorStream || monitorStream.getTracks().every(t => t.readyState === 'ended')) {
    setTimeout(createOffer, 10000); return;
  }

  rtcPeer = new RTCPeerConnection(RTC_CONFIG);
  monitorStream.getTracks().forEach(t => rtcPeer.addTrack(t, monitorStream));

  // Collect ICE candidates and send them
  rtcPeer.onicecandidate = async (e) => {
    if (e.candidate) {
      await postSignal('kiosk', 'ice-kiosk', e.candidate.toJSON());
    }
  };

  rtcPeer.onconnectionstatechange = () => {
    if (rtcPeer.connectionState === 'disconnected' || rtcPeer.connectionState === 'failed') {
      rtcReady = false;
      // Re-offer after 15s so teacher can reconnect
      setTimeout(createOffer, 15000);
    }
    if (rtcPeer.connectionState === 'connected') rtcReady = true;
  };

  const offer = await rtcPeer.createOffer({ offerToReceiveVideo: false });
  await rtcPeer.setLocalDescription(offer);
  await postSignal('kiosk', 'offer', { sdp: offer.sdp, type: offer.type });
  lastViewerSignalId = 0;
  lastOfferAt = Date.now();

  // Watchdog: if no connection is established within 25s, post a fresh offer
  // (prevents the deadlock where an offer expires in the DB and is never re-sent)
  const thisPeer = rtcPeer;
  setTimeout(() => {
    if (rtcPeer === thisPeer && rtcPeer.connectionState !== 'connected'
        && Date.now() - lastOfferAt >= 24000) {
      createOffer();
    }
  }, 25000);
}

async function pollViewerSignals() {
  try {
    const res  = await fetch(`${SIGNAL_URL}?role=kiosk&since=${lastViewerSignalId}`);
    const data = await res.json();
    for (const sig of (data.signals || [])) {
      lastViewerSignalId = sig.id;
      const payload = JSON.parse(sig.data);
      if (sig.type === 'answer' && rtcPeer && rtcPeer.signalingState === 'have-local-offer') {
        await rtcPeer.setRemoteDescription(new RTCSessionDescription(payload));
      } else if (sig.type === 'ice-viewer' && rtcPeer && rtcPeer.remoteDescription) {
        try { await rtcPeer.addIceCandidate(new RTCIceCandidate(payload)); } catch(e){}
      } else if (sig.type === 'request-offer'
                 && (!rtcPeer || rtcPeer.connectionState !== 'connected')
                 && Date.now() - lastOfferAt > 15000) {
        // Teacher is waiting for a fresh offer (old one expired in the DB)
        await createOffer();
      }
    }
  } catch(e) {}
}

async function postSignal(role, type, data) {
  try {
    await fetch(SIGNAL_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ role, type, data })
    });
  } catch(e) {}
}

// Start broadcast after 8s delay (let QR scanner init and grab camera first)
setTimeout(startMonitorBroadcast, 8000);
</script>
</body>
</html>
