<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../authentication/login.php'); exit;
}
require_once __DIR__ . '/../config/db.php';
$u          = APP_URL;
$teacherId  = (int)$_SESSION['user_id'];

// Recent messages sent by this teacher
$recent = $pdo = getDB();
$recent = $pdo->prepare(
    "SELECT id, message, type, created_at FROM kiosk_messages
     WHERE teacher_id = ? ORDER BY id DESC LIMIT 20"
);
$recent->execute([$teacherId]);
$recentMsgs = $recent->fetchAll();

$activePage = 'teacher-messages';
$pageTitle  = 'Send Kiosk Message';
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
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
            <li class="breadcrumb-item active">Send Kiosk Message</li>
          </ol>
        </nav>

        <h4 class="mb-4">📢 Send Message to Kiosk</h4>

        <div class="row g-4">
          <!-- Compose -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-header fw-semibold">Compose Message</div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label">Message</label>
                  <textarea id="msg-text" class="form-control" rows="3" maxlength="300"
                    placeholder="e.g. Please line up quietly outside the classroom…"></textarea>
                  <div class="form-text"><span id="msg-count">0</span>/300 characters</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Banner Type</label>
                  <select id="msg-type" class="form-select">
                    <option value="info">ℹ️ Info (blue)</option>
                    <option value="warning">⚠️ Warning (yellow)</option>
                    <option value="success">✅ Success (green)</option>
                  </select>
                </div>
                <button class="btn btn-primary w-100" id="btn-send-msg">📢 Send to Kiosk</button>
                <div class="form-text mt-2 text-center">
                  Message appears as a banner on the kiosk screen for 15 seconds.
                </div>
              </div>
            </div>
          </div>

          <!-- Preview -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-header fw-semibold">Preview</div>
              <div class="card-body">
                <div id="preview-banner" style="padding:.85rem 1.25rem;border-radius:10px;font-weight:600;font-size:.95rem;text-align:center;background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);color:#3b82f6;min-height:52px;transition:all .3s;">
                  Your message will appear here…
                </div>
                <p class="text-body-secondary small mt-2 mb-0 text-center">This is how it looks on the kiosk screen</p>
              </div>
            </div>

            <!-- Recent messages -->
            <div class="card mt-4">
              <div class="card-header fw-semibold">Recently Sent</div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-sm table-hover mb-0" id="recent-table">
                    <thead class="table-light">
                      <tr><th>Message</th><th>Type</th><th>Sent</th></tr>
                    </thead>
                    <tbody>
                      <?php if (empty($recentMsgs)): ?>
                      <tr><td colspan="3" class="text-center text-body-secondary py-3">No messages sent yet.</td></tr>
                      <?php else: foreach ($recentMsgs as $m):
                        $badge = match($m['type']) { 'warning' => 'warning', 'success' => 'success', default => 'info' };
                      ?>
                      <tr>
                        <td><?= htmlspecialchars($m['message']) ?></td>
                        <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($m['type']) ?></span></td>
                        <td class="text-body-secondary small"><?= date('M j, g:i A', strtotime($m['created_at'])) ?></td>
                      </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                  </table>
                </div>
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

  <!-- Toast -->
  <div id="msg-toast" style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;min-width:260px;padding:.85rem 1.25rem;border-radius:10px;color:#fff;font-weight:600;font-size:.9rem;display:none;box-shadow:0 4px 20px rgba(0,0,0,.25);">
    <span id="msg-toast-body"></span>
  </div>

  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
  <script>
  const textarea = document.getElementById('msg-text');
  const counter  = document.getElementById('msg-count');
  const preview  = document.getElementById('preview-banner');
  const typeEl   = document.getElementById('msg-type');

  const typeStyles = {
    info:    { bg:'rgba(59,130,246,.15)', border:'rgba(59,130,246,.3)', color:'#3b82f6' },
    warning: { bg:'rgba(234,179,8,.15)',  border:'rgba(234,179,8,.4)',  color:'#b45309' },
    success: { bg:'rgba(34,197,94,.15)',  border:'rgba(34,197,94,.3)',  color:'#15803d' },
  };

  function updatePreview() {
    const msg  = textarea.value.trim();
    const type = typeEl.value;
    const s    = typeStyles[type];
    counter.textContent = textarea.value.length;
    preview.style.background  = s.bg;
    preview.style.borderColor = s.border;
    preview.style.color       = s.color;
    preview.style.border      = '1px solid ' + s.border;
    preview.textContent = msg ? '📢 ' + msg : 'Your message will appear here…';
  }

  textarea.addEventListener('input', updatePreview);
  typeEl.addEventListener('change', updatePreview);

  document.getElementById('btn-send-msg').addEventListener('click', async () => {
    const msg  = textarea.value.trim();
    const type = typeEl.value;
    if (!msg) { showToast('Please enter a message.', '#dc3545'); return; }
    const btn = document.getElementById('btn-send-msg');
    btn.disabled = true; btn.textContent = 'Sending…';
    try {
      const res  = await fetch('<?= $u ?>/api/messages/send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, type })
      });
      const data = await res.json();
      if (data.success) {
        showToast('✅ Message sent to kiosk!', '#198754');
        textarea.value = '';
        counter.textContent = '0';
        updatePreview();
        prependRow(msg, type);
      } else {
        showToast('❌ ' + (data.message || 'Failed to send.'), '#dc3545');
      }
    } catch(e) { showToast('❌ Network error.', '#dc3545'); }
    btn.disabled = false; btn.textContent = '📢 Send to Kiosk';
  });

  function prependRow(msg, type) {
    const tbody = document.querySelector('#recent-table tbody');
    // Remove "no messages" row if present
    if (tbody.querySelector('td[colspan]')) tbody.innerHTML = '';
    const badge = { info:'info', warning:'warning', success:'success' }[type] || 'info';
    const now   = new Date().toLocaleString('en-PH', { month:'short', day:'numeric', hour:'numeric', minute:'2-digit' });
    const tr    = document.createElement('tr');
    tr.innerHTML = `<td>${msg.replace(/</g,'&lt;')}</td><td><span class="badge bg-${badge}">${type}</span></td><td class="text-body-secondary small">${now}</td>`;
    tbody.prepend(tr);
  }

  function showToast(text, bg) {
    const el = document.getElementById('msg-toast');
    el.style.background = bg;
    document.getElementById('msg-toast-body').textContent = text;
    el.style.display = 'block'; el.style.opacity = '1';
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => { el.style.display = 'none'; }, 300); }, 3500);
  }
  </script>
</body>
</html>
