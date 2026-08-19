<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../authentication/login.php'); exit;
}
require_once __DIR__ . '/../config/db.php';
$u = APP_URL;
$activePage = 'teacher-monitor';
$pageTitle  = 'Live Monitoring';
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
  <style>
  #monitor-video{width:100%;border-radius:12px;background:#000;display:block;min-height:240px;}
  .status-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:6px;}
  .status-dot.green{background:#22c55e;animation:pulse 1.5s infinite;}
  .status-dot.red{background:#ef4444;}
  .status-dot.yellow{background:#f59e0b;animation:pulse 1.5s infinite;}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
  .msg-collapsed{display:none;}
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>
    <div class="body flex-grow-1">
      <div class="container-lg px-4">

        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $u ?>/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Live Monitoring</li>
          </ol>
        </nav>

        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
          <h4 class="mb-0">📹 Live Kiosk Monitoring</h4>
          <span id="conn-status" class="badge bg-secondary fs-6">
            <span class="status-dot red" id="status-dot"></span>
            <span id="status-text">Waiting for kiosk…</span>
          </span>
        </div>

        <div class="row g-4">

          <!-- Live Feed -->
          <div class="col-lg-8">
            <div class="card">
              <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                <span>Kiosk Camera Feed</span>
                <small class="text-body-secondary" id="feed-note">Connecting…</small>
              </div>
              <div class="card-body p-2">
                <video id="monitor-video" autoplay playsinline muted></video>
                <div id="no-feed" class="text-center py-5 text-body-secondary" style="display:none!important;">
                  <div style="font-size:3rem;">📷</div>
                  <p class="mt-2">No feed yet — make sure the kiosk is open in a browser.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Side panel -->
          <div class="col-lg-4 d-flex flex-column gap-3">

            <!-- Connection info -->
            <div class="card">
              <div class="card-header fw-semibold">Connection</div>
              <div class="card-body small">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-body-secondary">State</span>
                  <strong id="rtc-state">—</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-body-secondary">ICE</span>
                  <strong id="ice-state">—</strong>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-body-secondary">Last signal</span>
                  <strong id="last-signal">—</strong>
                </div>
                <button class="btn btn-sm btn-outline-primary w-100 mt-3" id="btn-reconnect">🔄 Reconnect</button>
              </div>
            </div>

            <!-- Send Message (minimized) -->
            <div class="card">
              <div class="card-header fw-semibold d-flex align-items-center justify-content-between"
                   style="cursor:pointer;" id="msg-toggle">
                <span>📢 Send Kiosk Message</span>
                <span id="msg-chevron">▼</span>
              </div>
              <div id="msg-body" class="card-body msg-collapsed">
                <div class="mb-2">
                  <input type="text" id="msg-text" class="form-control form-control-sm"
                         maxlength="300" placeholder="Type a message…">
                </div>
                <div class="mb-2">
                  <select id="msg-type" class="form-select form-select-sm">
                    <option value="info">ℹ️ Info</option>
                    <option value="warning">⚠️ Warning</option>
                    <option value="success">✅ Success</option>
                  </select>
                </div>
                <button class="btn btn-primary btn-sm w-100" id="btn-send-msg">Send to Kiosk</button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
    <footer class="footer px-4 py-3 border-top">
      <div class="text-body-secondary small">&copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — ATS</div>
    </footer>
  </div>

  <div id="msg-toast" style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;min-width:240px;padding:.75rem 1.1rem;border-radius:10px;color:#fff;font-weight:600;font-size:.88rem;display:none;box-shadow:0 4px 20px rgba(0,0,0,.25);">
    <span id="msg-toast-body"></span>
  </div>

  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
  <script>
  const APP_URL    = <?= json_encode($u) ?>;
  const SIGNAL_URL = APP_URL + '/api/monitor/signal.php';
  const RTC_CONFIG = { iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' }
  ]};

  let rtcPeer = null, lastKioskSignalId = 0, pollTimer = null;

  // ── Status helpers ──────────────────────────────────────────────────────────
  function setStatus(state) {
    const dot  = document.getElementById('status-dot');
    const text = document.getElementById('status-text');
    const badge = document.getElementById('conn-status');
    const states = {
      waiting:     { dot:'red',    text:'Waiting for kiosk…',  badge:'bg-secondary' },
      connecting:  { dot:'yellow', text:'Connecting…',         badge:'bg-warning text-dark' },
      connected:   { dot:'green',  text:'Live',                badge:'bg-success' },
      disconnected:{ dot:'red',    text:'Disconnected',        badge:'bg-danger' },
    };
    const s = states[state] || states.waiting;
    dot.className  = 'status-dot ' + s.dot;
    text.textContent = s.text;
    badge.className  = 'badge fs-6 ' + s.badge;
    document.getElementById('feed-note').textContent =
      state === 'connected' ? 'Live feed active' : 'Waiting for kiosk camera…';
  }

  // ── WebRTC viewer ───────────────────────────────────────────────────────────
  async function startViewer() {
    if (rtcPeer) { try { rtcPeer.close(); } catch(e){} rtcPeer = null; }
    setStatus('connecting');

    rtcPeer = new RTCPeerConnection(RTC_CONFIG);

    rtcPeer.ontrack = (e) => {
      const video = document.getElementById('monitor-video');
      if (video.srcObject !== e.streams[0]) {
        video.srcObject = e.streams[0];
      }
    };

    rtcPeer.onicecandidate = async (e) => {
      if (e.candidate) await postSignal('viewer', 'ice-viewer', e.candidate.toJSON());
    };

    rtcPeer.onconnectionstatechange = () => {
      const s = rtcPeer.connectionState;
      document.getElementById('rtc-state').textContent = s;
      if (s === 'connected')    setStatus('connected');
      if (s === 'disconnected' || s === 'failed') {
        setStatus('disconnected');
        setTimeout(startViewer, 8000);
      }
    };

    rtcPeer.oniceconnectionstatechange = () => {
      document.getElementById('ice-state').textContent = rtcPeer.iceConnectionState;
    };

    // Start polling for kiosk offer
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(pollKioskSignals, 10000);
    // Poll immediately on start
    await pollKioskSignals();
  }

  async function pollKioskSignals() {
    try {
      const res  = await fetch(`${SIGNAL_URL}?role=viewer&since=${lastKioskSignalId}`);
      const data = await res.json();
      document.getElementById('last-signal').textContent = new Date().toLocaleTimeString();
      for (const sig of (data.signals || [])) {
        lastKioskSignalId = sig.id;
        const payload = JSON.parse(sig.data);
        if (sig.type === 'offer') {
          await rtcPeer.setRemoteDescription(new RTCSessionDescription(payload));
          const answer = await rtcPeer.createAnswer();
          await rtcPeer.setLocalDescription(answer);
          await postSignal('viewer', 'answer', { sdp: answer.sdp, type: answer.type });
          setStatus('connecting');
        } else if (sig.type === 'ice-kiosk' && rtcPeer.remoteDescription) {
          try { await rtcPeer.addIceCandidate(new RTCIceCandidate(payload)); } catch(e){}
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

  document.getElementById('btn-reconnect').addEventListener('click', () => {
    lastKioskSignalId = 0;
    startViewer();
  });

  // ── Collapsible message panel ───────────────────────────────────────────────
  document.getElementById('msg-toggle').addEventListener('click', () => {
    const body    = document.getElementById('msg-body');
    const chevron = document.getElementById('msg-chevron');
    const open    = !body.classList.contains('msg-collapsed');
    body.classList.toggle('msg-collapsed', open);
    chevron.textContent = open ? '▼' : '▲';
  });

  // ── Send message ────────────────────────────────────────────────────────────
  document.getElementById('btn-send-msg').addEventListener('click', async () => {
    const msg  = document.getElementById('msg-text').value.trim();
    const type = document.getElementById('msg-type').value;
    if (!msg) { showToast('Please enter a message.', '#dc3545'); return; }
    const btn = document.getElementById('btn-send-msg');
    btn.disabled = true; btn.textContent = 'Sending…';
    try {
      const res  = await fetch(APP_URL + '/api/messages/send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, type })
      });
      const data = await res.json();
      showToast(data.success ? '✅ Sent!' : '❌ ' + (data.message || 'Failed'), data.success ? '#198754' : '#dc3545');
      if (data.success) document.getElementById('msg-text').value = '';
    } catch(e) { showToast('❌ Network error', '#dc3545'); }
    btn.disabled = false; btn.textContent = 'Send to Kiosk';
  });

  function showToast(text, bg) {
    const el = document.getElementById('msg-toast');
    el.style.background = bg;
    document.getElementById('msg-toast-body').textContent = text;
    el.style.display = 'block'; el.style.opacity = '1';
    setTimeout(() => { el.style.opacity='0'; setTimeout(()=>{ el.style.display='none'; }, 300); }, 3000);
  }

  // Start on load
  startViewer();
  </script>
</body>
</html>
