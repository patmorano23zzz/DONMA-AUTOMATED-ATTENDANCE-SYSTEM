<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

$step = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$error = ''; $student = null;
$pdo = getDB(); $u = APP_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lrn'])) {
    $lrn = preg_replace('/\D/', '', trim($_POST['lrn']));
    if (strlen($lrn) < 10 || strlen($lrn) > 12) {
        $error = 'LRN must be 10–12 digits.';
    } else {
        $s = $pdo->prepare('SELECT * FROM students WHERE lrn = ? LIMIT 1');
        $s->execute([$lrn]); $student = $s->fetch();
        if (!$student) { $error = 'LRN not found. Contact your teacher or school registrar.'; }
        elseif (($student['enrollment_status'] ?? 'approved') === 'pending') { $error = 'Your enrollment is pending admin approval.'; }
        else { $_SESSION['reg_student_id'] = $student['id']; $_SESSION['reg_lrn'] = $lrn; header('Location: register.php?step=2'); exit; }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && (int)$_POST['step'] === 3 && isset($_POST['faces']) && isset($_SESSION['reg_student_id']) && !isset($_POST['password'])) {
    // Step 2 ? 3 transition: store faces in session, not in form
    $_SESSION['reg_faces'] = $_POST['faces'] ?? '[]';
    // $step is already set to 3 from $_POST['step']
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && isset($_SESSION['reg_student_id'])) {
    $password = $_POST['password'] ?? ''; $confirm = $_POST['confirm'] ?? '';
    $terms = $_POST['terms'] ?? '';
    // Read faces from session (set in step 2?3 transition)
    $faces = $_SESSION['reg_faces'] ?? '[]';
    if ($faces === '[]' || $faces === null) {
        $error = 'Face capture data is missing. Please go back and complete face capture.';
    } elseif (strlen($password) < 6) { $error = 'Password must be at least 6 characters.'; }
    elseif ($password !== $confirm) { $error = 'Passwords do not match.'; }
    elseif (!$terms) { $error = 'You must accept the terms and conditions.'; }
    else {
        $sid = $_SESSION['reg_student_id'];
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $faceData = json_decode($faces, true) ?: [];
        $dir = __DIR__ . '/../assets/faces/' . $sid;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        foreach ($faceData as $idx => $img) {
            $raw = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $img));
            if ($raw && strlen($raw) <= 2 * 1024 * 1024) {
                file_put_contents($dir . '/reg_' . $idx . '.jpg', $raw);
            }
        }
        $pdo->prepare('UPDATE students SET password_hash=?,terms_accepted=1,registered_at=NOW() WHERE id=?')->execute([$hash, $sid]);
        $lrn = $_SESSION['reg_lrn']; $qrStr = 'DCMMES-' . $lrn;
        unset($_SESSION['reg_student_id'], $_SESSION['reg_lrn'], $_SESSION['reg_faces']);
        header('Location: register.php?step=4&qr=' . urlencode($qrStr)); exit;
    }
}

if (in_array($step, [2,3]) && isset($_SESSION['reg_student_id'])) {
    $s = $pdo->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
    $s->execute([$_SESSION['reg_student_id']]); $student = $s->fetch();
    if (!$student) { header('Location: register.php'); exit; }
}
$qrVal = $_GET['qr'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Registration | DONMA ATS</title>
<link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png?v=202608092157">
<link href="<?= $u ?>/css/style.css" rel="stylesheet">
<script src="<?= $u ?>/js/donma-modal.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--blue:#1a6b3a;--blue-d:#145530;--blue-l:#d4edda;--green:#1a6b3a;--gold:#c9a227;--red:#dc2626;--radius:16px;--shadow:0 8px 32px rgba(26,86,219,.13)}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes scanLine{0%{top:0}100%{top:100%}}
@keyframes popIn{from{opacity:0;transform:scale(.85)}to{opacity:1;transform:scale(1)}}
body{font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh;background:#f0f7f2;display:flex;flex-direction:column}
.reg-header{background:linear-gradient(135deg,#0d3320,#1a6b3a);padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.reg-brand{display:flex;align-items:center;gap:.65rem;color:#fff;text-decoration:none}
.reg-brand-text{font-weight:700;font-size:.9rem;line-height:1.2}
.reg-brand-sub{font-size:.68rem;opacity:.6;font-weight:400;display:block}
.reg-header-nav{display:flex;gap:.5rem;flex-wrap:wrap}
.reg-nav-link{color:rgba(255,255,255,.7);font-size:.78rem;padding:.35rem .8rem;border:1px solid rgba(255,255,255,.18);border-radius:50px;text-decoration:none;transition:all .2s}
.reg-nav-link:hover{color:#fff;background:rgba(255,255,255,.1)}
.reg-main{flex:1;display:flex;align-items:center;justify-content:center;padding:1.5rem 1rem}
.reg-wrap{width:100%;max-width:500px;animation:fadeUp .5s ease both}
/* Step progress */
.step-progress{display:flex;align-items:center;justify-content:center;margin-bottom:2rem}
.step-item{display:flex;flex-direction:column;align-items:center;gap:.3rem}
.step-circle{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;border:2px solid #cbd5e1;background:#fff;color:#94a3b8;transition:all .3s;position:relative}
.step-item.active .step-circle{border-color:var(--blue);background:var(--blue);color:#fff;box-shadow:0 0 0 4px rgba(26,107,58,.2)}
.step-item.done .step-circle{border-color:var(--green);background:var(--green);color:#fff}
.step-item.done .step-num{display:none}
.step-item.done .step-circle::after{content:'?';font-size:.9rem}
.step-label{font-size:.68rem;font-weight:600;color:#94a3b8}
.step-item.active .step-label{color:var(--blue)}
.step-item.done .step-label{color:var(--green)}
.step-connector{width:44px;height:2px;background:#e2e8f0;margin:0 4px 18px;transition:background .3s}
.step-connector.done{background:var(--green)}
/* Card */
.reg-card{background:#fff;border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;border:1px solid #e2e8f0}
.reg-card-header{background:linear-gradient(135deg,var(--blue),#2d8b50);padding:1.5rem 1.75rem 1.25rem;color:#fff;text-align:center}
.reg-card-header h2{font-size:1.15rem;font-weight:700;margin-bottom:.25rem}
.reg-card-header p{font-size:.82rem;opacity:.8}
.reg-card-header .student-name{font-size:1rem;font-weight:700;background:rgba(255,255,255,.18);border-radius:8px;padding:.4rem .9rem;display:inline-block;margin-top:.5rem}
.reg-card-body{padding:1.75rem}
/* Fields */
.field-group{margin-bottom:1.1rem}
.field-group label{display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:.4rem}
.field-input{width:100%;padding:.7rem 1rem;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.9rem;color:#1e293b;background:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
.field-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,86,219,.12)}
.field-input.mono{font-family:monospace;letter-spacing:.06em;font-size:1rem}
.field-hint{font-size:.74rem;color:#94a3b8;margin-top:.3rem}
/* Alert */
.reg-alert{border-radius:10px;padding:.75rem 1rem;font-size:.85rem;display:flex;align-items:center;gap:.6rem;margin-bottom:1.25rem}
.reg-alert-danger{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.reg-alert-warning{background:#fef3c7;border:1px solid #fde68a;color:#92400e}
/* Buttons */
.reg-btn{width:100%;padding:.8rem;border:none;border-radius:50px;font-size:.95rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;transition:transform .2s,box-shadow .2s}
.reg-btn-primary{background:linear-gradient(135deg,var(--blue),#2d8b50);color:#fff}
.reg-btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(26,86,219,.4)}
.reg-btn-primary:disabled{opacity:.6;cursor:not-allowed;transform:none}
.reg-btn-outline{background:#fff;border:1.5px solid #e2e8f0;color:#374151}
.reg-btn-outline:hover{background:#f8fafc}
.reg-btn-success{background:linear-gradient(135deg,var(--green),#22c55e);color:#fff}
.reg-btn-success:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(22,163,74,.35)}
.btn-row{display:flex;gap:.6rem}
.btn-row .reg-btn{flex:1}
/* Checkbox */
.reg-check{display:flex;align-items:flex-start;gap:.65rem;margin-bottom:1rem}
.reg-check input{width:18px;height:18px;accent-color:var(--blue);flex-shrink:0;margin-top:.15rem}
.reg-check label{font-size:.82rem;color:#374151;line-height:1.55}
.reg-check a{color:var(--blue)}
/* Password strength */
.pw-strength{display:flex;gap:.3rem;margin-top:.4rem}
.pw-bar{flex:1;height:3px;border-radius:2px;background:#e2e8f0;transition:background .3s}
.pw-label{font-size:.7rem;margin-top:.25rem}
/* Bottom links */
.reg-links{text-align:center;margin-top:1.25rem;font-size:.8rem;color:#94a3b8}
.reg-links a{color:var(--blue);text-decoration:none}
.reg-links a:hover{text-decoration:underline}
/* Face capture */
.face-wrap{position:relative;border-radius:12px;overflow:hidden;background:#111;margin-bottom:1rem;aspect-ratio:4/3}
.face-wrap video{width:100%;height:100%;object-fit:cover;display:block}
.face-scan-overlay{position:absolute;inset:0;pointer-events:none;border:3px solid rgba(26,86,219,.5);border-radius:12px}
.face-scan-line{position:absolute;left:10%;right:10%;height:2px;background:linear-gradient(90deg,transparent,#3b82f6,transparent);animation:scanLine 2s linear infinite}
.face-corner{position:absolute;width:22px;height:22px;border-color:var(--blue);border-style:solid;border-width:0}
.face-corner.tl{top:10px;left:10px;border-top-width:3px;border-left-width:3px;border-radius:4px 0 0 0}
.face-corner.tr{top:10px;right:10px;border-top-width:3px;border-right-width:3px;border-radius:0 4px 0 0}
.face-corner.bl{bottom:10px;left:10px;border-bottom-width:3px;border-left-width:3px;border-radius:0 0 0 4px}
.face-corner.br{bottom:10px;right:10px;border-bottom-width:3px;border-right-width:3px;border-radius:0 0 4px 0}
.face-badge{position:absolute;bottom:10px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.72);color:#fff;font-size:.72rem;font-weight:600;padding:.3rem .9rem;border-radius:50px;white-space:nowrap;backdrop-filter:blur(4px)}
.pose-dots{display:flex;justify-content:center;gap:.5rem;margin:.6rem 0 .4rem}
.pose-dot{width:38px;height:38px;border-radius:50%;border:2px solid #e2e8f0;background:#f8fafc;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;color:#94a3b8;transition:all .3s;flex-direction:column;gap:1px}
.pose-dot small{font-size:.55rem;opacity:.7}
.pose-dot.active{border-color:var(--blue);background:var(--blue-l);color:var(--blue);animation:pulse 1.2s ease-in-out infinite}
.pose-dot.done{border-color:var(--green);background:var(--green);color:#fff}
.pose-instruction{text-align:center;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:.5rem;min-height:1.3em}
.sample-progress{display:flex;gap:.4rem;justify-content:center;margin-bottom:1rem}
.sample-dot{width:28px;height:8px;border-radius:4px;background:#e2e8f0;transition:background .25s}
.sample-dot.filled{background:var(--blue)}
/* Done screen */
.done-wrap{text-align:center;padding:.5rem 0}
.done-icon{font-size:4.5rem;animation:popIn .5s ease both;display:block;margin-bottom:.5rem}
.done-title{font-size:1.35rem;font-weight:800;color:#1e293b;margin-bottom:.4rem}
.done-sub{font-size:.875rem;color:#64748b;margin-bottom:1.5rem;line-height:1.65}
.qr-card-box{border:2px solid #e2e8f0;border-radius:14px;padding:1.25rem;margin:0 auto 1.5rem;max-width:230px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.08)}
.qr-card-school{font-size:.6rem;font-weight:700;color:#1e293b;margin-bottom:.5rem;line-height:1.3}
.qr-card-name{font-size:.82rem;font-weight:700;margin-top:.5rem;color:#1e293b}
.qr-card-lrn{font-size:.68rem;font-family:monospace;color:#64748b}
.qr-card-sec{font-size:.65rem;color:#94a3b8}
.done-btn-col{display:flex;flex-direction:column;gap:.65rem}
/* ── Mobile ── */
@media(max-width:480px){
  .reg-main{padding:1rem .75rem}
  .reg-card-body{padding:1.25rem}
  .reg-card-header{padding:1.1rem 1.25rem 1rem}
  .reg-card-header h2{font-size:1rem}
  .reg-brand-text{font-size:.8rem}
  .step-circle{width:32px;height:32px;font-size:.75rem}
  .step-label{font-size:.6rem}
  .step-connector{width:28px}
  .field-group{margin-bottom:.85rem}
  .field-input{padding:.6rem .85rem;font-size:.85rem}
  .reg-btn{padding:.7rem;font-size:.88rem}
  .face-wrap{aspect-ratio:4/3}
  .pose-dot{width:32px;height:32px;font-size:.6rem}
  .done-icon{font-size:3rem}
  .done-title{font-size:1.1rem}
}
</style>
</head>
<body>
<!-- -- HEADER ----------------------------------------------------------- -->
<header class="reg-header">
  <a href="<?= $u ?>/landing.php" class="reg-brand">
    <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.25);">
    <div>
      <div class="reg-brand-text">Don Marcelo C. Marty Elementary School</div>
      <span class="reg-brand-sub">Student Registration Portal</span>
    </div>
  </a>
  <nav class="reg-header-nav">
    <a href="<?= $u ?>/kiosk.php" class="reg-nav-link">📷 Kiosk</a>
    <a href="<?= $u ?>/authentication/login.php?force=1" class="reg-nav-link">Staff Login</a>
  </nav>
</header>

<!-- -- MAIN ---------------------------------------------------------------- -->
<main class="reg-main">
<div class="reg-wrap">

  <!-- Step Progress (hidden on done screen) -->
  <?php if ($step < 4): ?>
  <div class="step-progress">
    <?php
    $stepDefs = [1=>'LRN', 2=>'Face', 3=>'Password', 4=>'Done'];
    $i = 0;
    foreach ($stepDefs as $n => $label):
        $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
        if ($i > 0): ?>
        <div class="step-connector <?= $n <= $step ? 'done' : '' ?>"></div>
    <?php endif; $i++; ?>
    <div class="step-item <?= $cls ?>">
      <div class="step-circle"><span class="step-num"><?= $n ?></span></div>
      <div class="step-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- -- STEP 1 — LRN ------------------------------------------------------ -->
  <?php if ($step === 1): ?>
  <div class="reg-card">
    <div class="reg-card-header">
      <h2>🎓 Enter Your LRN</h2>
      <p>Your Learner Reference Number from your DepEd records</p>
    </div>
    <div class="reg-card-body">
      <?php if ($error): ?>
      <div class="reg-alert reg-alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" autocomplete="off">
        <div class="field-group">
          <label for="lrn">Learner Reference Number (10–12 digits)</label>
          <input class="field-input mono" id="lrn" name="lrn" type="text"
            maxlength="12" placeholder="e.g. 123456789012"
            value="<?= htmlspecialchars($_POST['lrn'] ?? '') ?>"
            autofocus required>
          <div class="field-hint">Numbers only. Remove any spaces or dashes.</div>
        </div>
        <button class="reg-btn reg-btn-primary" type="submit">
          Continue <span>→</span>
        </button>
      </form>
    </div>
  </div>

  <!-- -- STEP 2 — Face Capture --------------------------------------------- -->
  <?php elseif ($step === 2): ?>
  <div class="reg-card">
    <div class="reg-card-header">
      <h2>📸 Face Capture</h2>
      <p>Biometric verification for secure attendance</p>
      <div class="student-name">
        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
      </div>
    </div>
    <div class="reg-card-body">
      <div class="face-wrap" id="cam-wrap">
        <video id="cam-video" autoplay playsinline muted></video>
        <div class="face-scan-overlay">
          <div class="face-scan-line"></div>
          <div class="face-corner tl"></div>
          <div class="face-corner tr"></div>
          <div class="face-corner bl"></div>
          <div class="face-corner br"></div>
        </div>
        <div class="face-badge" id="face-badge">Initializing camera…</div>
      </div>
      <canvas id="cam-canvas" style="display:none"></canvas>

      <div class="pose-instruction" id="pose-instruction">Pose 1 of 4 — Look straight at the camera</div>
      <div class="pose-dots" id="pose-dots">
        <div class="pose-dot active"><span>1</span><small>Front</small></div>
        <div class="pose-dot"><span>2</span><small>Left</small></div>
        <div class="pose-dot"><span>3</span><small>Right</small></div>
        <div class="pose-dot"><span>4</span><small>Down</small></div>
      </div>
      <div class="sample-progress" id="sample-progress">
        <div class="sample-dot"></div>
        <div class="sample-dot"></div>
        <div class="sample-dot"></div>
      </div>

      <div class="btn-row">
        <button class="reg-btn reg-btn-primary" id="btn-capture" disabled>
          📷 Capture Sample
        </button>
        <button class="reg-btn reg-btn-outline" id="btn-next-pose" disabled>
          Next Pose →
        </button>
      </div>
      <div style="text-align:center;margin-top:.75rem">
        <a href="register.php" style="font-size:.78rem;color:#94a3b8;text-decoration:none;">← Start Over</a>
      </div>
    </div>
  </div>

  <form id="face-form" method="POST" action="register.php" style="display:none">
    <input type="hidden" name="step" value="3">
    <input type="hidden" name="faces" value="">
  </form>

  <script>
  const POSES = [
    'Pose 1 of 4 — Look straight at the camera',
    'Pose 2 of 4 — Tilt head slightly left',
    'Pose 3 of 4 — Tilt head slightly right',
    'Pose 4 of 4 — Look slightly downward',
  ];
  let currentPose = 0, samplesThisPose = 0, allCaptures = [], stream = null;

  async function startCam() {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
      document.getElementById('cam-video').srcObject = stream;
      document.getElementById('face-badge').textContent = 'Camera ready — click Capture Sample';
      document.getElementById('btn-capture').disabled = false;
    } catch(e) {
      document.getElementById('face-badge').textContent = 'Camera unavailable';
      DonmaModal.alert({ title: 'Camera Error', message: 'Could not access the camera. Please allow camera permissions and refresh.', type: 'danger' });
    }
  }
  startCam();

  function updateSampleDots() {
    document.querySelectorAll('#sample-progress .sample-dot').forEach((d, i) => {
      d.classList.toggle('filled', i < samplesThisPose);
    });
  }

  document.getElementById('btn-capture').addEventListener('click', () => {
    const video = document.getElementById('cam-video');
    const canvas = document.getElementById('cam-canvas');
    canvas.width = video.videoWidth || 320; canvas.height = video.videoHeight || 240;
    canvas.getContext('2d').drawImage(video, 0, 0);
    allCaptures.push(canvas.toDataURL('image/jpeg', 0.75));
    samplesThisPose++;
    updateSampleDots();
    document.getElementById('face-badge').textContent = samplesThisPose + ' / 3 samples captured';
    if (samplesThisPose >= 3) {
      document.getElementById('btn-capture').disabled = true;
      document.getElementById('btn-next-pose').disabled = false;
      if (currentPose < POSES.length - 1) {
        document.getElementById('face-badge').textContent = '✓ Pose complete — click Next Pose';
      } else {
        document.getElementById('face-badge').textContent = '? All poses done!';
      }
    }
  });

  document.getElementById('btn-next-pose').addEventListener('click', () => {
    const dots = document.querySelectorAll('#pose-dots .pose-dot');
    dots[currentPose].className = 'pose-dot done';
    dots[currentPose].innerHTML = '?';
    currentPose++; samplesThisPose = 0;
    updateSampleDots();

    if (currentPose >= POSES.length) {
      if (stream) stream.getTracks().forEach(t => t.stop());
      document.getElementById('face-form').querySelector('[name="faces"]').value = JSON.stringify(allCaptures);
      document.getElementById('face-form').submit();
      return;
    }
    dots[currentPose].className = 'pose-dot active';
    document.getElementById('pose-instruction').textContent = POSES[currentPose];
    document.getElementById('face-badge').textContent = 'Ready — capture sample';
    document.getElementById('btn-capture').disabled = false;
    document.getElementById('btn-next-pose').disabled = true;
  });
  </script>

  <!-- -- STEP 3 — Password -------------------------------------------------- -->
  <?php elseif ($step === 3): ?>
  <div class="reg-card">
    <div class="reg-card-header">
      <h2>🔐 Set Your Password</h2>
      <p>Create a secure password to protect your account</p>
    </div>
    <div class="reg-card-body">
      <?php if ($error): ?>
      <div class="reg-alert reg-alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" autocomplete="off">
        <input type="hidden" name="step" value="3">
        <div class="field-group">
          <label for="password">Password <span style="font-weight:400;color:#94a3b8;">(min. 6 characters)</span></label>
          <input class="field-input" id="password" name="password" type="password"
            placeholder="Enter a strong password" required autocomplete="new-password">
          <div class="pw-strength" id="pw-strength">
            <div class="pw-bar" id="pw-b1"></div>
            <div class="pw-bar" id="pw-b2"></div>
            <div class="pw-bar" id="pw-b3"></div>
            <div class="pw-bar" id="pw-b4"></div>
          </div>
          <div class="pw-label" id="pw-label" style="color:#94a3b8;">Enter a password</div>
        </div>
        <div class="field-group">
          <label for="confirm">Confirm Password</label>
          <input class="field-input" id="confirm" name="confirm" type="password"
            placeholder="Repeat your password" required autocomplete="new-password">
        </div>
        <div class="reg-check">
          <input type="checkbox" id="terms" name="terms" value="1" required>
          <label for="terms">
            I accept the <a href="#" onclick="DonmaModal.alert({title:'Terms &amp; Conditions',message:'By registering, you agree to use the DONMA Attendance System only for legitimate school attendance purposes.',type:'info'});return false;">terms and conditions</a>
            for the DONMA Attendance Tracking System
          </label>
        </div>
        <button class="reg-btn reg-btn-primary" type="submit" id="btn-submit">
          ✅ Complete Registration
        </button>
      </form>
    </div>
  </div>
  <script>
  const pwInput = document.getElementById('password');
  const bars = [document.getElementById('pw-b1'),document.getElementById('pw-b2'),document.getElementById('pw-b3'),document.getElementById('pw-b4')];
  const label = document.getElementById('pw-label');
  const colors = ['#ef4444','#f97316','#eab308','#22c55e'];
  const labels = ['Too short','Weak','Fair','Strong'];
  pwInput.addEventListener('input', () => {
    const v = pwInput.value;
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v) && /[0-9]/.test(v)) score++;
    if (/[^a-zA-Z0-9]/.test(v)) score++;
    bars.forEach((b, i) => { b.style.background = i < score ? colors[Math.min(score-1,3)] : '#e2e8f0'; });
    label.textContent = v.length ? labels[Math.max(0,score-1)] : 'Enter a password';
    label.style.color = v.length ? colors[Math.min(score-1,3)] : '#94a3b8';
  });
  </script>

  <!-- -- STEP 4 — Done ------------------------------------------------------ -->
  <?php elseif ($step === 4): ?>
  <div class="reg-card">
    <div class="reg-card-body">
      <div class="done-wrap">
        <span class="done-icon">🎉</span>
        <div class="done-title">Registration Complete!</div>
        <p class="done-sub">
          Your QR code is ready. Present it at the kiosk every day<br>
          to record your attendance automatically.
        </p>

        <?php if ($qrVal): ?>
        <div class="qr-card-box">
          <div class="qr-card-school">Don Marcelo C. Marty Elementary School<br>
            <span style="font-weight:400;">Attendance Tracking System</span></div>
          <div id="qr-output" style="display:flex;justify-content:center;"></div>
          <?php
          $regLrn = str_replace('DCMMES-','', $qrVal);
          $regStu = $pdo->prepare('SELECT * FROM students WHERE lrn=? LIMIT 1');
          $regStu->execute([$regLrn]); $regStudent = $regStu->fetch();
          ?>
          <div class="qr-card-name"><?= $regStudent ? htmlspecialchars($regStudent['last_name'].', '.$regStudent['first_name']) : '' ?></div>
          <div class="qr-card-lrn">LRN: <?= htmlspecialchars($regLrn) ?></div>
          <div class="qr-card-sec"><?= $regStudent ? htmlspecialchars($regStudent['grade_section']) : '' ?></div>
        </div>
        <div class="qr-card-lrn" style="margin-bottom:1.25rem;font-size:.6rem;"><?= htmlspecialchars($qrVal) ?></div>
        <?php endif; ?>

        <div class="done-btn-col">
          <a href="<?= $u ?>/api/students/qr.php?lrn=<?= urlencode($regLrn ?? '') ?>"
             class="reg-btn reg-btn-success" target="_blank">
            🖨 Download / Print QR Code
          </a>
          <a href="<?= $u ?>/kiosk.php" class="reg-btn reg-btn-primary">
            📷 Go to Student Kiosk
          </a>
          <a href="<?= $u ?>/authentication/login.php?force=1" class="reg-btn reg-btn-outline">
            Staff Login
          </a>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= $u ?>/js/qrcode.min.js"></script>
  <script>
  <?php if ($qrVal): ?>
  new QRCode(document.getElementById('qr-output'), {
    text: <?= json_encode($qrVal) ?>,
    width: 160, height: 160,
    colorDark: '#000', colorLight: '#fff',
    correctLevel: QRCode.CorrectLevel.H
  });
  <?php endif; ?>
  </script>
  <?php endif; ?>

  <div class="reg-links">
    <a href="<?= $u ?>/kiosk.php">← Back to Kiosk</a>
    &nbsp;—&nbsp;
    <a href="<?= $u ?>/authentication/login.php?force=1">Admin / Teacher Login</a>
  </div>

</div>
</main>

<footer style="text-align:center;padding:.75rem;font-size:.72rem;color:#94a3b8;border-top:1px solid #e2e8f0;background:#fff;">
  &copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — Automated Attendance Tracking System
</footer>

<script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
</body>
</html>