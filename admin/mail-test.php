<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../authentication/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/mail.php';

$pdo = getDB();
$u = APP_URL;
$stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ? AND role = "admin" LIMIT 1');
$stmt->execute([(int)$_SESSION['user_id']]);
$admin = $stmt->fetch();
$success = '';
$error = '';
$resetSuccess = '';
$resetError = '';

if (!$admin) {
    http_response_code(403);
    exit('Administrator account not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'send_test') {
        if (strtolower(trim((string)app_config('MAIL_TRANSPORT', 'smtp'))) !== 'brevo') {
            $error = 'The configured mail transport is not Brevo. Set mail_transport to brevo in config/deployment.php.';
        } else {
            try {
                $messageId = sendBrevoDiagnosticEmail($admin['email'], $admin['name']);
                mail_log('Admin diagnostic email accepted for ' . $admin['email'] . '. Message ID: ' . $messageId);
                $success = 'Brevo accepted the test email. Message ID: ' . $messageId
                    . '. Check the inbox and spam folder for ' . $admin['email'] . '.';
            } catch (Throwable $e) {
                mail_log('Admin diagnostic email failed: ' . $e->getMessage());
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'send_reset') {
        $requestedEmail = $_POST['email'] ?? '';
        $requestedEmail = is_string($requestedEmail) ? trim($requestedEmail) : '';

        if (!filter_var($requestedEmail, FILTER_VALIDATE_EMAIL)) {
            $resetError = 'Enter a valid email address.';
        } else {
            $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$requestedEmail]);
            $user = $stmt->fetch();

            if (!$user) {
                $resetError = 'No user account has this exact email address. Check the email value in the users table.';
            } elseif (strtolower(trim((string)app_config('MAIL_TRANSPORT', 'smtp'))) !== 'brevo') {
                $resetError = 'The configured mail transport is not Brevo. Set mail_transport to brevo in config/deployment.php.';
            } else {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')
                    ->execute([$user['id']]);
                $pdo->prepare(
                    'INSERT INTO password_reset_tokens (token_hash, user_id, expires_at)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))'
                )->execute([$tokenHash, $user['id']]);

                $resetUrl = rtrim(APP_URL, '/') . '/authentication/reset-password.php?token=' . rawurlencode($token);
                try {
                    $messageId = sendPasswordResetEmail($user['email'], $user['name'], $resetUrl);
                    mail_log('Admin-triggered password reset accepted for user #' . $user['id']
                        . ' (' . $user['email'] . '). Message ID: ' . $messageId);
                    $resetSuccess = 'Brevo accepted a real reset email for the matching account. Message ID: '
                        . $messageId . '. Check Brevo Transactional Logs for this message ID.';
                } catch (Throwable $e) {
                    mail_log('Admin-triggered password reset failed for ' . $user['email'] . ': ' . $e->getMessage());
                    $pdo->prepare('DELETE FROM password_reset_tokens WHERE token_hash = ?')
                        ->execute([$tokenHash]);
                    $resetError = $e->getMessage();
                }
            }
        }
    } else {
        $error = 'Unknown email test action.';
    }
}

$activePage = 'mail-test';
$pageTitle = 'Email Delivery Test';
$base = './../';

$mailLogFile = __DIR__ . '/../assets/cache/mail.log';
$mailLogTail = '';
if (is_file($mailLogFile)) {
    $mailLogLines = file($mailLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($mailLogLines)) {
        $mailLogTail = implode("\n", array_slice($mailLogLines, -25));
    }
}
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
        <h4 class="mb-3">Email Delivery Test</h4>
        <div class="row">
          <div class="col-lg-7">
            <div class="card">
              <div class="card-body">
                <p class="text-body-secondary">
                  Send a diagnostic email through the configured Brevo API to your administrator email:
                  <strong><?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?></strong>.
                  This test does not change any passwords.
                </p>
                <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="send_test">
                  <button class="btn btn-primary" type="submit">Send Test Email</button>
                  <a class="btn btn-outline-secondary" href="<?= $u ?>/index.php">Back to Dashboard</a>
                </form>
              </div>
            </div>
            <div class="card mt-3">
              <div class="card-header fw-semibold">Test a Password Reset</div>
              <div class="card-body">
                <p class="text-body-secondary">
                  Enter the account email to verify that it exists and send a real, one-time reset link.
                  This invalidates any previously issued reset link for that account.
                </p>
                <?php if ($resetSuccess): ?>
                <div class="alert alert-success"><?= htmlspecialchars($resetSuccess, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($resetError): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($resetError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="send_reset">
                  <div class="mb-3">
                    <label class="form-label" for="reset-email">Account email</label>
                    <input class="form-control" id="reset-email" name="email" type="email"
                      value="<?= htmlspecialchars(is_string($_POST['email'] ?? null) ? $_POST['email'] : '', ENT_QUOTES, 'UTF-8') ?>"
                      required>
                  </div>
                  <button class="btn btn-warning" type="submit">Send Real Reset Link</button>
                </form>
              </div>
            </div>
            <div class="card mt-3">
              <div class="card-header fw-semibold">Recent Mail Log</div>
              <div class="card-body">
                <?php if ($mailLogTail !== ''): ?>
                <pre class="mb-0 small" style="white-space:pre-wrap;max-height:18rem;overflow:auto;"><?= htmlspecialchars($mailLogTail, ENT_QUOTES, 'UTF-8') ?></pre>
                <?php else: ?>
                <p class="text-body-secondary mb-0">No mail log entries yet. Send a test email to generate some.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
</body>
</html>
