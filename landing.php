<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
$u = APP_URL;
try {
    $pdo = getDB();
    $totalStudents = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $todayPresent  = $pdo->prepare('SELECT COUNT(DISTINCT student_id) FROM attendance WHERE DATE(time_in)=CURDATE()');
    $todayPresent->execute(); $todayPresent = $todayPresent->fetchColumn();
} catch (Exception $e) { $totalStudents = 0; $todayPresent = 0; }
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DONMA ATS — Don Marcelo C. Marty Elementary School</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link href="<?= $u ?>/css/style.css" rel="stylesheet">
  <style>
/* ── Reset ─────────────────────────────────────── */
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior:smooth; }
a { text-decoration:none; color:inherit; }
img { max-width:100%; }
body { font-family:'Segoe UI',system-ui,sans-serif; overflow-x:hidden; transition:background .3s,color .3s; }

/* ── Light theme (default) ─────────────────────── */
:root {
  --bg:          #f5f7f0;
  --bg2:         #ffffff;
  --bg3:         #eef2e6;
  --surface:     #ffffff;
  --text:        #1a2e1a;
  --text2:       #3d5a3d;
  --muted:       #6b7f6b;
  --border:      #d4e0c8;
  --green:       #1a6b3a;
  --green-d:     #145530;
  --green-l:     #e8f5e9;
  --green-m:     #2d8b50;
  --gold:        #c9a227;
  --gold-d:      #a8841f;
  --gold-l:      #fef9e7;
  --primary:     #1a6b3a;
  --accent:      #c9a227;
  --radius:      12px;
  --shadow:      0 4px 24px rgba(26,107,58,.12);
  --shadow-lg:   0 12px 48px rgba(26,107,58,.18);
  --nav-bg:      rgba(26,107,58,.96);
  --hero-from:   #0d3320;
  --hero-to:     #1a6b3a;
  --hero-text:   #ffffff;
  --hero-muted:  rgba(255,255,255,.75);
}

/* ── Dark theme ─────────────────────────────────── */
[data-theme="dark"] {
  --bg:          #0a1f0f;
  --bg2:         #0f2915;
  --bg3:         #0d2312;
  --surface:     #132b18;
  --text:        #e8f5e9;
  --text2:       #a5d6a7;
  --muted:       #81a881;
  --border:      #1e4a24;
  --green:       #4caf78;
  --green-d:     #388e5a;
  --green-l:     #1a3d22;
  --green-m:     #66bb6a;
  --gold:        #f0c040;
  --gold-d:      #d4a832;
  --gold-l:      #2a2200;
  --primary:     #4caf78;
  --accent:      #f0c040;
  --shadow:      0 4px 24px rgba(0,0,0,.4);
  --shadow-lg:   0 12px 48px rgba(0,0,0,.5);
  --nav-bg:      rgba(10,31,15,.97);
  --hero-from:   #050f07;
  --hero-to:     #0d2e15;
  --hero-text:   #e8f5e9;
  --hero-muted:  rgba(232,245,233,.7);
}

body { background:var(--bg); color:var(--text); }
</style>

<style>
/* ── Animations ─────────────────────────────────── */
@keyframes fadeUp   { from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)} }
@keyframes float    { 0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)} }
@keyframes pulse    { 0%,100%{transform:scale(1)}50%{transform:scale(1.05)} }
@keyframes bounce   { 0%,100%{transform:translateY(0)}40%{transform:translateY(-8px)} }
@keyframes gradMove { 0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%} }
@keyframes shimmer  { 0%{opacity:.4}50%{opacity:1}100%{opacity:.4} }

.anim-fade-up  { animation:fadeUp .7s ease both; }
.delay-1 { animation-delay:.15s; }
.delay-2 { animation-delay:.3s;  }
.delay-3 { animation-delay:.45s; }
.delay-4 { animation-delay:.6s;  }

/* ── Navbar ─────────────────────────────────────── */
.navbar {
  position:fixed; top:0; left:0; right:0; z-index:1060;
  background:var(--nav-bg);
  backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
  border-bottom:1px solid rgba(255,255,255,.08);
  padding:.85rem 2rem;
  display:flex; align-items:center; justify-content:space-between; gap:1rem;
  transition:background .3s, box-shadow .3s;
}
.navbar.scrolled { box-shadow:0 2px 20px rgba(0,0,0,.3); }
.nav-brand {
  display:flex; align-items:center; gap:.7rem;
  color:#fff; font-weight:700; font-size:1rem; text-decoration:none; flex-shrink:0;
}
.nav-brand-logo {
  width:36px; height:36px; border-radius:50%; object-fit:cover;
  border:2px solid var(--gold); flex-shrink:0;
}
.nav-brand-text { line-height:1.15; }
.nav-brand-text strong { display:block; font-size:.9rem; letter-spacing:.02em; }
.nav-brand-text span   { font-size:.65rem; opacity:.65; font-weight:400; display:block; }

.nav-center { display:flex; align-items:center; gap:.35rem; }
.nav-center a {
  color:rgba(255,255,255,.8); font-size:.84rem; padding:.42rem .85rem;
  border-radius:6px; text-decoration:none; transition:color .2s,background .2s;
}
.nav-center a:hover { color:#fff; background:rgba(255,255,255,.1); }

.nav-right { display:flex; align-items:center; gap:.5rem; }
/* Theme toggle */
.theme-toggle {
  background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
  border-radius:50px; color:#fff; cursor:pointer;
  width:34px; height:34px; display:flex; align-items:center; justify-content:center;
  font-size:1rem; transition:background .2s; flex-shrink:0;
}
.theme-toggle:hover { background:rgba(255,255,255,.22); }
/* Login button — gold */
.nav-login {
  background:var(--gold); color:#1a2e1a !important; font-weight:700;
  padding:.42rem 1.1rem; border-radius:50px; font-size:.84rem;
  transition:background .2s, transform .15s;
}
.nav-login:hover { background:var(--gold-d); transform:translateY(-1px); color:#1a2e1a !important; }

/* Burger */
.nav-burger {
  display:none; background:none; border:none; cursor:pointer;
  padding:.35rem; z-index:1070;
  flex-direction:column; align-items:center; justify-content:center;
  gap:5px; width:34px; height:34px;
}
.nav-burger span {
  display:block; width:20px; height:2px; background:#fff;
  border-radius:2px; transition:transform .3s,opacity .3s;
}
.nav-burger.open span:nth-child(1){ transform:translateY(7px) rotate(45deg); }
.nav-burger.open span:nth-child(2){ opacity:0; transform:scaleX(0); }
.nav-burger.open span:nth-child(3){ transform:translateY(-7px) rotate(-45deg); }

@media(max-width:768px){
  .navbar { padding:.7rem 1rem; }
  .nav-burger { display:flex; }
  .nav-center {
    position:fixed; top:0; left:0; right:0; bottom:0;
    background:rgba(8,24,12,.97); backdrop-filter:blur(18px);
    flex-direction:column; align-items:center; justify-content:center;
    gap:.75rem; z-index:1055; padding:5rem 1.5rem 3rem;
    transform:translateX(100%); transition:transform .32s cubic-bezier(.4,0,.2,1);
    overflow-y:auto;
  }
  .nav-center.open { transform:translateX(0); }
  .nav-center a {
    font-size:1rem; font-weight:600; padding:.8rem 1.5rem;
    width:100%; max-width:260px; text-align:center;
    border-radius:50px; border:1px solid rgba(255,255,255,.15);
    background:rgba(255,255,255,.04);
  }
  .nav-center a:hover { background:rgba(255,255,255,.12); border-color:rgba(255,255,255,.3); color:#fff; }
  .nav-login { background:var(--gold); color:#1a2e1a !important; border-color:var(--gold); }
}
</style>

<style>
/* ── Buttons ─────────────────────────────────────── */
.btn {
  display:inline-flex; align-items:center; gap:.5rem;
  padding:.75rem 1.75rem; border-radius:50px; font-weight:600;
  font-size:.95rem; cursor:pointer; border:2px solid transparent;
  transition:transform .2s,box-shadow .2s,background .2s,color .2s;
}
.btn:hover    { transform:translateY(-2px); }
.btn:active   { transform:translateY(0); }
.btn-gold     { background:var(--gold); color:#1a2e1a; }
.btn-gold:hover { background:var(--gold-d); box-shadow:0 6px 20px rgba(201,162,39,.4); }
.btn-green    { background:var(--green); color:#fff; }
.btn-green:hover { background:var(--green-d); box-shadow:0 6px 20px rgba(26,107,58,.4); }
.btn-outline-w { background:transparent; color:#fff; border-color:rgba(255,255,255,.55); }
.btn-outline-w:hover { background:rgba(255,255,255,.1); border-color:#fff; }
.btn-outline-g { background:transparent; color:var(--green); border-color:var(--green); }
.btn-outline-g:hover { background:var(--green-l); }
.btn-lg { padding:.9rem 2.2rem; font-size:1.05rem; }
.btn-sm { padding:.45rem 1.1rem; font-size:.83rem; }
.btn-pulse { animation:pulse 2.2s ease-in-out infinite; }

/* ── Hero ─────────────────────────────────────────── */
.hero {
  min-height:100vh; display:flex; align-items:center;
  background:linear-gradient(135deg, var(--hero-from) 0%, var(--hero-to) 60%, #0a2d18 100%);
  background-size:300% 300%; animation:gradMove 10s ease infinite;
  padding:6rem 2rem 4rem; position:relative; overflow:hidden;
}
.hero::before {
  content:''; position:absolute; inset:0; pointer-events:none;
  background:
    radial-gradient(ellipse at 75% 45%, rgba(201,162,39,.12) 0%, transparent 55%),
    radial-gradient(ellipse at 20% 80%, rgba(26,107,58,.25) 0%, transparent 50%);
}
/* Subtle pattern overlay */
.hero::after {
  content:''; position:absolute; inset:0; pointer-events:none; opacity:.04;
  background-image:repeating-linear-gradient(45deg, #fff 0, #fff 1px, transparent 0, transparent 50%);
  background-size:20px 20px;
}
.hero-inner {
  max-width:1200px; margin:0 auto; width:100%; position:relative; z-index:1;
  display:grid; grid-template-columns:1fr 1fr; gap:4rem; align-items:center;
}
.hero-eyebrow {
  display:inline-flex; align-items:center; gap:.5rem;
  background:rgba(201,162,39,.18); border:1px solid rgba(201,162,39,.4);
  color:#f0c040; padding:.38rem 1rem; border-radius:50px;
  font-size:.78rem; font-weight:700; margin-bottom:1.25rem; letter-spacing:.04em;
}
.hero-eyebrow span { width:7px; height:7px; border-radius:50%; background:var(--gold); animation:pulse 1.5s infinite; }
.hero h1 { font-size:clamp(2rem,5vw,3.2rem); font-weight:900; color:var(--hero-text); line-height:1.1; margin-bottom:1rem; }
.hero h1 em { font-style:normal; color:var(--gold); }
.hero-sub { color:var(--hero-muted); font-size:1.02rem; line-height:1.75; margin-bottom:2rem; max-width:460px; }
.hero-actions { display:flex; gap:.85rem; flex-wrap:wrap; }
.hero-visual { display:flex; justify-content:center; }

/* Live stats card */
.stats-card {
  background:rgba(255,255,255,.06); backdrop-filter:blur(18px);
  border:1px solid rgba(255,255,255,.13); border-radius:20px;
  padding:1.75rem; max-width:320px; width:100%;
  animation:float 4s ease-in-out infinite;
}
.stats-card-header {
  display:flex; align-items:center; gap:.75rem; margin-bottom:1.25rem;
  padding-bottom:1rem; border-bottom:1px solid rgba(255,255,255,.1);
}
.stats-card-header img { width:44px; height:44px; object-fit:contain; }
.stats-card-header .title { color:#fff; font-weight:700; font-size:.9rem; }
.stats-card-header .sub   { color:rgba(255,255,255,.5); font-size:.72rem; }
.live-dot { width:8px; height:8px; border-radius:50%; background:#4caf50; animation:pulse 1.5s infinite; }

.stat-row { display:grid; grid-template-columns:1fr 1fr; gap:.85rem; margin-top:.5rem; }
.stat-box {
  background:rgba(255,255,255,.08); border-radius:12px;
  padding:.9rem .75rem; text-align:center;
  border:1px solid rgba(255,255,255,.07);
}
.stat-box .num { font-size:1.9rem; font-weight:900; color:#fff; }
.stat-box .lbl { font-size:.68rem; color:rgba(255,255,255,.5); margin-top:.2rem; }

.feature-row {
  display:flex; flex-direction:column; gap:.6rem; margin-bottom:1rem;
}
.feature-pill {
  display:flex; align-items:center; gap:.75rem;
  background:rgba(255,255,255,.07); border-radius:10px; padding:.7rem .9rem;
  border:1px solid rgba(255,255,255,.07);
}
.feature-pill .icon { font-size:1.25rem; flex-shrink:0; }
.feature-pill .label { color:#fff; font-size:.82rem; font-weight:600; }
.feature-pill .desc  { color:rgba(255,255,255,.45); font-size:.7rem; }

/* Scroll cue */
.scroll-cue {
  position:absolute; bottom:1.75rem; left:50%; transform:translateX(-50%);
  animation:bounce 2s infinite; opacity:.5;
}

@media(max-width:900px){
  .hero-inner { grid-template-columns:1fr; gap:2.5rem; text-align:center; }
  .hero-actions { justify-content:center; }
  .hero-sub { margin:0 auto 2rem; }
  .stats-card { max-width:100%; }
  .hero { padding:5.5rem 1.25rem 3rem; }
}

/* ── Section base ─────────────────────────────────── */
section { padding:5rem 2rem; }
.section-inner { max-width:1200px; margin:0 auto; }
.section-tag {
  display:inline-block; background:var(--green-l); color:var(--green);
  font-size:.75rem; font-weight:700; padding:.32rem .85rem; border-radius:50px;
  margin-bottom:.75rem; letter-spacing:.05em; text-transform:uppercase;
}
[data-theme="dark"] .section-tag { background:var(--green-l); color:var(--green); }
.section-title { font-size:clamp(1.6rem,3.5vw,2.3rem); font-weight:800; margin-bottom:.75rem; color:var(--text); }
.section-sub   { color:var(--muted); font-size:.98rem; max-width:560px; line-height:1.75; }
.text-center { text-align:center; }
.text-center .section-sub { margin:0 auto; }

/* ── Features ────────────────────────────────────── */
.features-bg { background:var(--bg3); }
.features-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:1.5rem; margin-top:2.5rem; }
.feature-card {
  background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
  padding:1.75rem; transition:transform .3s,box-shadow .3s; position:relative; overflow:hidden;
}
.feature-card::before {
  content:''; position:absolute; top:0; left:0; right:0; height:3px;
  background:linear-gradient(90deg,var(--green),var(--gold));
  transform:scaleX(0); transform-origin:left; transition:transform .3s;
}
.feature-card:hover { transform:translateY(-5px); box-shadow:var(--shadow); }
.feature-card:hover::before { transform:scaleX(1); }
.feature-icon {
  width:50px; height:50px; border-radius:13px; background:var(--green-l);
  display:flex; align-items:center; justify-content:center;
  margin-bottom:1.1rem; font-size:1.4rem; transition:transform .3s;
}
.feature-card:hover .feature-icon { transform:scale(1.1) rotate(-5deg); }
.feature-card h3 { font-size:1rem; font-weight:700; margin-bottom:.4rem; color:var(--text); }
.feature-card p  { font-size:.86rem; color:var(--muted); line-height:1.65; }

/* ── Steps ────────────────────────────────────────── */
.steps-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:2rem; margin-top:2.5rem; }
.step { text-align:center; }
.step-num {
  width:56px; height:56px; border-radius:50%; margin:0 auto 1rem;
  background:linear-gradient(135deg,var(--green),var(--gold));
  color:#fff; font-size:1.3rem; font-weight:900;
  display:flex; align-items:center; justify-content:center;
  box-shadow:0 4px 16px rgba(26,107,58,.35);
}
.step h3 { font-weight:700; margin-bottom:.4rem; font-size:.95rem; color:var(--text); }
.step p  { font-size:.83rem; color:var(--muted); }

/* ── Registration section ────────────────────────── */
.reg-bg { background:var(--bg3); }
.reg-grid { display:grid; grid-template-columns:1fr 1fr; gap:4rem; align-items:center; }
.reg-steps-list { list-style:none; display:flex; flex-direction:column; gap:.7rem; margin-bottom:1.75rem; }
.reg-step-item { display:flex; align-items:center; gap:.75rem; font-size:.93rem; color:var(--text); }
.reg-step-num {
  width:28px; height:28px; border-radius:50%;
  background:linear-gradient(135deg,var(--green),var(--gold));
  color:#fff; display:flex; align-items:center; justify-content:center;
  font-size:.72rem; font-weight:800; flex-shrink:0;
}
.already-card {
  background:var(--surface); border-radius:20px; padding:2.25rem;
  box-shadow:var(--shadow-lg); border:1px solid var(--border); text-align:center;
}
.already-card h3 { font-size:1.05rem; font-weight:700; margin:.85rem 0 .5rem; color:var(--text); }
.already-card p  { font-size:.85rem; color:var(--muted); margin-bottom:1.5rem; }
.btn-block { width:100%; justify-content:center; }

@media(max-width:768px){ .reg-grid { grid-template-columns:1fr !important; gap:2rem !important; } }

/* ── CTA strip ───────────────────────────────────── */
.cta-strip {
  padding:5rem 2rem; text-align:center; position:relative; overflow:hidden;
  background:linear-gradient(135deg, #0d3320 0%, #1a6b3a 50%, #0a2d18 100%);
}
.cta-strip::before {
  content:''; position:absolute; inset:0; pointer-events:none;
  background:radial-gradient(ellipse at 50% 50%, rgba(201,162,39,.15) 0%, transparent 65%);
}
.cta-strip h2 { font-size:clamp(1.6rem,3.5vw,2.3rem); font-weight:800; color:#fff; margin-bottom:.75rem; position:relative; }
.cta-strip p  { color:rgba(255,255,255,.7); max-width:500px; margin:0 auto 2rem; position:relative; }
.cta-btns { display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; position:relative; }

/* ── Footer ──────────────────────────────────────── */
.site-footer {
  background:var(--bg2); border-top:1px solid var(--border);
  padding:2rem; text-align:center; font-size:.84rem; color:var(--muted);
}
.site-footer strong { color:var(--text); }
.site-footer a { color:var(--green); }
.site-footer a:hover { color:var(--gold); }

/* ── Scroll reveal ───────────────────────────────── */
.reveal { opacity:0; transform:translateY(22px); transition:opacity .65s, transform .65s; }
.reveal.visible { opacity:1; transform:translateY(0); }

/* ── Mobile section padding ─────────────────────── */
@media(max-width:768px){ section { padding:3rem 1.25rem; } }
</style>

</head>
<body>

<!-- ══ NAVBAR ═══════════════════════════════════════════════════════════ -->
<nav class="navbar" id="navbar">
  <a href="#" class="nav-brand">
    <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" class="nav-brand-logo">
    <div class="nav-brand-text">
      <strong>DONMA <span style="color:var(--gold);">ATS</span></strong>
      <span>Don Marcelo C. Marty</span>
    </div>
  </a>

  <div class="nav-center" id="nav-links">
    <a href="#features">Features</a>
    <a href="#how-it-works">How It Works</a>
    <a href="#register">Registration</a>
    <a href="<?= $u ?>/kiosk.php">Student Kiosk</a>
    <a href="<?= $u ?>/authentication/login.php?force=1" class="nav-login">Login</a>
  </div>

  <div class="nav-right">
    <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark/light mode" title="Toggle theme">
      <span id="theme-icon">🌙</span>
    </button>
    <button class="nav-burger" id="nav-burger" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- ══ HERO ══════════════════════════════════════════════════════════════ -->
<section class="hero" id="home">
  <div class="hero-inner">
    <!-- Left: text -->
    <div>
      <div class="hero-eyebrow anim-fade-up">
        <span></span> School Year 2025–2026
      </div>
      <h1 class="anim-fade-up delay-1">
        Smart Attendance<br>for <em>Don Marcelo<br>C. Marty</em><br>Elementary
      </h1>
      <p class="hero-sub anim-fade-up delay-2">
        Automated, biometric-powered attendance tracking with QR codes and face verification.
        Real-time monitoring for students, teachers, and administrators.
      </p>
      <div class="hero-actions anim-fade-up delay-3">
        <a href="<?= $u ?>/kiosk.php" class="btn btn-gold btn-lg btn-pulse">
          📷 Open Student Kiosk
        </a>
        <a href="#register" class="btn btn-outline-w btn-lg">
          📝 Student Registration
        </a>
      </div>
    </div>

    <!-- Right: live stats card -->
    <div class="hero-visual anim-fade-up delay-4">
      <div class="stats-card">
        <div class="stats-card-header">
          <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="Logo">
          <div>
            <div class="title">Live Overview</div>
            <div class="sub" style="display:flex;align-items:center;gap:.4rem;">
              <span class="live-dot"></span> System Active
            </div>
          </div>
        </div>
        <div class="feature-row">
          <div class="feature-pill">
            <span class="icon">🎓</span>
            <div><div class="label">QR Attendance</div><div class="desc">Scan &amp; record instantly</div></div>
          </div>
          <div class="feature-pill">
            <span class="icon">🔐</span>
            <div><div class="label">Face Verification</div><div class="desc">Biometric security</div></div>
          </div>
        </div>
        <div class="stat-row">
          <div class="stat-box">
            <div class="num" id="stat-students"><?= number_format($totalStudents) ?></div>
            <div class="lbl">Total Students</div>
          </div>
          <div class="stat-box">
            <div class="num" id="stat-present"><?= number_format($todayPresent) ?></div>
            <div class="lbl">Present Today</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="scroll-cue">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" fill="none" stroke="#fff" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
  </div>
</section>


<!-- ══ FEATURES ══════════════════════════════════════════════════════════ -->
<section id="features" class="features-bg">
  <div class="section-inner">
    <div class="text-center reveal">
      <span class="section-tag">Features</span>
      <h2 class="section-title">Everything you need for modern attendance</h2>
      <p class="section-sub">Built specifically for Don Marcelo C. Marty Elementary School — simple for students, powerful for administrators.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card reveal delay-1">
        <div class="feature-icon">📷</div>
        <h3>QR Code Scanning</h3>
        <p>Each student gets a unique QR code (DCMMES-LRN). Scan at the kiosk for instant time-in and time-out recording.</p>
      </div>
      <div class="feature-card reveal delay-2">
        <div class="feature-icon">🔐</div>
        <h3>Face Verification</h3>
        <p>Biometric face capture during registration and attendance. Override option available for edge cases.</p>
      </div>
      <div class="feature-card reveal delay-3">
        <div class="feature-icon">📊</div>
        <h3>Real-time Dashboard</h3>
        <p>Live stats — present, late, absent counts. Weekly trend charts and per-section breakdowns for teachers and admins.</p>
      </div>
      <div class="feature-card reveal delay-1">
        <div class="feature-icon">📋</div>
        <h3>Attendance Reports</h3>
        <p>Filter by date range, section, or student. Export to CSV for DepEd reporting. Per-student attendance percentage.</p>
      </div>
      <div class="feature-card reveal delay-2">
        <div class="feature-icon">🖨️</div>
        <h3>QR Code Printing</h3>
        <p>Print individual or batch QR codes for all students. Clean printable cards with school branding.</p>
      </div>
      <div class="feature-card reveal delay-3">
        <div class="feature-icon">👥</div>
        <h3>Multi-role Access</h3>
        <p>Admin manages everything. Teachers see their section only. Students use the kiosk — no login required.</p>
      </div>
    </div>
  </div>
</section>

<!-- ══ HOW IT WORKS ══════════════════════════════════════════════════════ -->
<section id="how-it-works">
  <div class="section-inner">
    <div class="text-center reveal">
      <span class="section-tag">How It Works</span>
      <h2 class="section-title">Simple 4-step process</h2>
      <p class="section-sub">From student registration to daily attendance — the whole flow in under a minute.</p>
    </div>
    <div class="steps-grid">
      <div class="step reveal delay-1">
        <div class="step-num">1</div>
        <h3>Student Registers</h3>
        <p>Enter LRN, capture face in 4 poses, set password and accept terms.</p>
      </div>
      <div class="step reveal delay-2">
        <div class="step-num">2</div>
        <h3>Get QR Code</h3>
        <p>System generates a unique QR (DCMMES-LRN). Print and keep it.</p>
      </div>
      <div class="step reveal delay-3">
        <div class="step-num">3</div>
        <h3>Scan at Kiosk</h3>
        <p>Scan QR code on arrival. Face verification confirms identity. Attendance recorded instantly.</p>
      </div>
      <div class="step reveal delay-4">
        <div class="step-num">4</div>
        <h3>Track &amp; Report</h3>
        <p>Teachers and admins view live data, generate reports, and export records.</p>
      </div>
    </div>
  </div>
</section>

<!-- ══ REGISTRATION ═══════════════════════════════════════════════════════ -->
<section id="register" class="reg-bg">
  <div class="section-inner">
    <div class="reg-grid">
      <div class="reveal">
        <span class="section-tag">Students</span>
        <h2 class="section-title">New student? Register now.</h2>
        <p class="section-sub" style="margin-bottom:1.5rem;">
          No account needed. Just enter your LRN, capture your face, and your QR code is ready in minutes.
        </p>
        <ul class="reg-steps-list">
          <?php foreach(['Enter your 10–12 digit LRN','Capture face (4 poses)','Set your password','Print your QR code'] as $i => $step): ?>
          <li class="reg-step-item">
            <span class="reg-step-num"><?= $i+1 ?></span>
            <?= htmlspecialchars($step) ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <a href="<?= $u ?>/students/register.php" class="btn btn-green btn-lg">
          📝 Start Registration →
        </a>
      </div>
      <div class="reveal delay-2">
        <div class="already-card">
          <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157"
               alt="School Logo" style="width:72px;height:72px;object-fit:contain;">
          <h3>Already registered?</h3>
          <p>Go to the Student Kiosk to record your attendance by scanning your QR code.</p>
          <a href="<?= $u ?>/kiosk.php" class="btn btn-green btn-block">
            📷 Open Student Kiosk
          </a>
          <p style="margin-top:.85rem;font-size:.78rem;color:var(--muted);">No login required at the kiosk</p>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ══ CTA STRIP ═════════════════════════════════════════════════════════ -->
<div class="cta-strip">
  <h2 class="reveal">Ready to go digital?</h2>
  <p class="reveal delay-1">Admins and teachers — sign in to access the dashboard, manage students, and generate reports.</p>
  <div class="cta-btns reveal delay-2">
    <a href="<?= $u ?>/authentication/login.php?force=1" class="btn btn-gold btn-lg">🔑 Admin / Teacher Login</a>
    <a href="<?= $u ?>/authentication/register.php" class="btn btn-outline-w btn-lg">📋 Teacher Registration</a>
  </div>
</div>

<!-- ══ FOOTER ════════════════════════════════════════════════════════════ -->
<footer class="site-footer">
  <div style="margin-bottom:.4rem;">
    <strong>Don Marcelo C. Marty Elementary School</strong>
    &nbsp;—&nbsp; Automated Attendance Tracking System
  </div>
  <div>
    &copy; <?= date('Y') ?> All rights reserved
    &nbsp;|&nbsp; <a href="<?= $u ?>/authentication/login.php?force=1">Staff Login</a>
    &nbsp;|&nbsp; <a href="<?= $u ?>/kiosk.php">Student Kiosk</a>
    &nbsp;|&nbsp; <a href="<?= $u ?>/students/register.php">Register</a>
  </div>
</footer>

<script>
// ── Theme toggle ─────────────────────────────────────────────────────────
const html      = document.documentElement;
const themeBtn  = document.getElementById('theme-toggle');
const themeIcon = document.getElementById('theme-icon');
const THEME_KEY = 'donma-theme';

function applyTheme(theme) {
  html.setAttribute('data-theme', theme);
  themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
  themeBtn.title = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
}

// Load saved preference
const saved = localStorage.getItem(THEME_KEY);
if (saved) applyTheme(saved);
else if (window.matchMedia('(prefers-color-scheme: dark)').matches) applyTheme('dark');
else applyTheme('light');

themeBtn.addEventListener('click', () => {
  const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  applyTheme(next);
  localStorage.setItem(THEME_KEY, next);
});

// ── Navbar scroll effect ──────────────────────────────────────────────────
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 40);
});

// ── Mobile burger ─────────────────────────────────────────────────────────
const burger    = document.getElementById('nav-burger');
const navLinks  = document.getElementById('nav-links');

function openMenu() {
  navLinks.classList.add('open');
  burger.classList.add('open');
  burger.setAttribute('aria-expanded', 'true');
  document.body.style.overflow = 'hidden';
}
function closeMenu() {
  navLinks.classList.remove('open');
  burger.classList.remove('open');
  burger.setAttribute('aria-expanded', 'false');
  document.body.style.overflow = '';
}

burger.addEventListener('click', () => {
  navLinks.classList.contains('open') ? closeMenu() : openMenu();
});
navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });

// ── Scroll reveal ─────────────────────────────────────────────────────────
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// ── Animated stat counters ────────────────────────────────────────────────
function animateCount(el, target) {
  if (!el || target === 0) return;
  let cur = 0;
  const step = Math.max(1, Math.floor(target / 40));
  const t = setInterval(() => {
    cur = Math.min(cur + step, target);
    el.textContent = cur.toLocaleString();
    if (cur >= target) clearInterval(t);
  }, 40);
}
const statsObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      animateCount(document.getElementById('stat-students'), <?= (int)$totalStudents ?>);
      animateCount(document.getElementById('stat-present'),  <?= (int)$todayPresent ?>);
      statsObs.disconnect();
    }
  });
}, { threshold: 0.3 });
const statsCard = document.querySelector('.stats-card');
if (statsCard) statsObs.observe(statsCard);
</script>
</body>
</html>
