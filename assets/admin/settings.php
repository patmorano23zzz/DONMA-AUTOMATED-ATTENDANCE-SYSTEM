<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php'); exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
$pdo     = getDB();
$u       = APP_URL;
$success = false;
$error   = '';

// Ensure settings row exists
$pdo->exec("INSERT IGNORE INTO settings (id) VALUES (1)");
$settings = $pdo->query('SELECT * FROM settings WHERE id = 1')->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $cutOff     = $_POST['cut_off_time']    ?? '08:00';
    $schoolName = trim($_POST['school_name']  ?? '');
    $schoolYear = trim($_POST['school_year']  ?? '');
    $bioApi     = trim($_POST['biometric_api'] ?? '');

    if (!$schoolName) { $error = 'School name is required.'; }
    else {
        $pdo->prepare(
            'UPDATE settings SET cut_off_time=?, school_name=?, school_year=?, biometric_api=? WHERE id=1'
        )->execute([$cutOff . ':00', $schoolName, $schoolYear, $bioApi ?: null]);
        $settings = $pdo->query('SELECT * FROM settings WHERE id = 1')->fetch();
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Settings saved successfully.'];
        header('Location: settings.php'); exit;
    }
}

$activePage = 'settings';
$pageTitle  = 'Settings';
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include __DIR__ . '/../includes/head.php'; ?></head>
<body>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>
    <div class="body flex-grow-1">
      <div class="container-lg px-4">

        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $u ?>/index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Settings</li>
          </ol>
        </nav>

        <h4 class="mb-4">System Settings</h4>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <script>document.addEventListener('DOMContentLoaded',()=>DonmaModal.toast({message:<?= json_encode($error) ?>,type:'danger'}));</script>
        <?php endif; ?>

        <div class="row g-4">
          <!-- General Settings -->
          <div class="col-lg-6">
            <div class="card h-100">
              <div class="card-header fw-semibold">General Settings</div>
              <div class="card-body">
                <form method="POST">
                  <?= csrf_field() ?>
                  <div class="mb-3">
                    <label class="form-label">School Name</label>
                    <input class="form-control" name="school_name" type="text"
                      value="<?= htmlspecialchars($settings['school_name'] ?? 'Don Marcelo C. Marty Elementary School') ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">School Year</label>
                    <input class="form-control" name="school_year" type="text"
                      placeholder="e.g. 2025-2026"
                      value="<?= htmlspecialchars($settings['school_year'] ?? '') ?>">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Late Cut-off Time</label>
                    <input class="form-control" name="cut_off_time" type="time"
                      value="<?= htmlspecialchars(substr($settings['cut_off_time'] ?? '08:00:00', 0, 5)) ?>">
                    <div class="form-text">Students arriving after this time are marked <strong>Late</strong>.</div>
                  </div>
                  <button class="btn btn-primary" type="submit">Save Settings</button>
                </form>
              </div>
            </div>
          </div>

          <!-- System Info + Biometric -->
          <div class="col-lg-6">
            <div class="card">
              <div class="card-header fw-semibold">System Information</div>
              <div class="card-body">
                <table class="table table-sm mb-0">
                  <tr><th class="text-body-secondary w-50">PHP Version</th><td><?= PHP_VERSION ?></td></tr>
                  <tr><th class="text-body-secondary">Database</th><td>MySQL (<?= DB_NAME ?>)</td></tr>
                  <tr><th class="text-body-secondary">Server</th><td><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') ?></td></tr>
                  <tr><th class="text-body-secondary">Server Time</th><td><?= date('Y-m-d H:i:s') ?></td></tr>
                </table>
              </div>
            </div>

            <div class="card mt-3">
              <div class="card-header fw-semibold">Biometric Device</div>
              <div class="card-body">
                <p class="text-body-secondary small mb-2">
                  For dedicated biometric hardware (ZKTeco, etc.), configure the API endpoint below.
                </p>
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="school_name" value="<?= htmlspecialchars($settings['school_name'] ?? '') ?>">
                  <input type="hidden" name="school_year" value="<?= htmlspecialchars($settings['school_year'] ?? '') ?>">
                  <input type="hidden" name="cut_off_time" value="<?= htmlspecialchars(substr($settings['cut_off_time'] ?? '08:00:00', 0, 5)) ?>">
                  <div class="mb-2">
                    <label class="form-label small">API URL <span class="text-body-secondary">(optional)</span></label>
                    <input class="form-control form-control-sm" type="url" name="biometric_api"
                      placeholder="http://192.168.1.x:4370/api"
                      value="<?= htmlspecialchars($settings['biometric_api'] ?? '') ?>">
                  </div>
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" id="btn-test-bio" type="button">Test Connection</button>
                    <button class="btn btn-sm btn-primary" type="submit">Save</button>
                    <span id="bio-status" class="small align-self-center text-body-secondary"></span>
                  </div>
                </form>
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
  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
  <script>
    document.getElementById('btn-test-bio')?.addEventListener('click', () => {
      document.getElementById('bio-status').textContent = 'Testing…';
      setTimeout(() => {
        document.getElementById('bio-status').textContent = 'No device connected (configure IP above).';
      }, 1200);
    });
  </script>
</body>
</html>
