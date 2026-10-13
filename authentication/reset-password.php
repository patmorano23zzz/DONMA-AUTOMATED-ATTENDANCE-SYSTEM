<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/mail.php';

$u       = APP_URL;
$pdo     = getDB();
$error   = '';
$notice  = '';
$success = false;
$mailErrorDetail = '';
$submittedToken = $_POST['token'] ?? $_GET['token'] ?? '';
$token   = is_string($submittedToken) ? trim($submittedToken) : '';
$isResetMode = $token !== '';
$tokenHash   = '';

if ($isResetMode) {
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store, private');
}

if ($isResetMode) {
    if (preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare(
            'SELECT token_hash FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        if (!$stmt->fetch()) {
            $error = 'This reset link is invalid or has expired. Request another reset.';
        }
    } else {
        $error = 'This reset link is invalid or has expired. Request another reset.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if ($isResetMode) {
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirm  = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

        if ($tokenHash && !$error && strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($tokenHash && !$error && $password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif ($tokenHash && !$error) {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'SELECT user_id FROM password_reset_tokens
                 WHERE token_hash = ? AND expires_at > NOW() FOR UPDATE'
            );
            $stmt->execute([$tokenHash]);
            $reset = $stmt->fetch();

            if (!$reset) {
                $pdo->rollBack();
                $error = 'This reset link is invalid or has expired. Request another reset.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([$hash, $reset['user_id']]);
                $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')
                    ->execute([$reset['user_id']]);
                $pdo->commit();
                $success = true;
            }
        }
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!rate_limit('password_reset_' . $ip, 5, 900)) {
            $error = 'Too many reset requests. Please wait before trying again.';
        } else {
            $submittedEmail = $_POST['email'] ?? '';
            $email = is_string($submittedEmail) ? trim($submittedEmail) : '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Enter a valid email address.';
            } else {
                $pdo->prepare('DELETE FROM password_reset_tokens WHERE expires_at <= NOW()')->execute();
                $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')
                        ->execute([$user['id']]);
                    $pdo->prepare(
                        'INSERT INTO password_reset_tokens (token_hash, user_id, expires_at)
                         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))'
                    )->execute([$tokenHash, $user['id']]);

                    $appUrl = rtrim(APP_URL, '/');
                    $resetUrl = $appUrl . '/authentication/reset-password.php?token=' . rawurlencode($token);
                    try {
                        $messageId = sendPasswordResetEmail($user['email'], $user['name'], $resetUrl);
                        mail_log('Password reset email accepted for user #' . $user['id']
                            . ' (' . $user['email'] . '). Message ID: ' . $messageId);
                    } catch (Throwable $e) {
                        mail_log('Password reset email FAILED for user #' . $user['id']
                            . ' (' . $user['email'] . '): ' . $e->getMessage());
                        $mailErrorDetail = $e->getMessage();
                        $pdo->prepare('DELETE FROM password_reset_tokens WHERE token_hash = ?')
                            ->execute([$tokenHash]);
                    }
                } else {
                    mail_log('Password reset requested for an email with no matching account.');
                }

                $notice = 'If an account exists for this email, check your inbox for reset instructions. '
                    . "If you don't receive them, contact the administrator.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
  <title>Reset Password | DONMA ATS</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $u ?>/assets/favicon/favicon-32x32.png?v=202608092157">
  <link rel="stylesheet" href="<?= $u ?>/vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?= $u ?>/css/vendors/simplebar.css">
  <link href="<?= $u ?>/css/style.css" rel="stylesheet">
  <script src="<?= $u ?>/js/config.js"></script>
  <script src="<?= $u ?>/js/color-modes.js"></script>
</head>
<body class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
<div class="container" style="max-width:28rem">
  <div class="d-flex flex-column gap-4">
    <div class="text-center">
      <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157" alt="School Logo" class="mb-2" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
      <h1 class="h5 fw-bold mb-0">Reset Password</h1>
      <p class="text-body-secondary small">DONMA Attendance Tracking System</p>
    </div>

    <div class="card p-4">
      <div class="card-body d-flex flex-column gap-3">
        <?php if ($success): ?>
        <div class="alert alert-success mb-0">Your password has been reset. You can now sign in with your new password.</div>
        <a class="btn btn-primary w-100" href="<?= $u ?>/authentication/login.php?force=1">Back to Login</a>
        <?php elseif ($isResetMode): ?>
        <p class="text-body-secondary small text-center mb-0">Choose a new password for your account.</p>
        <?php if ($error): ?>
        <div class="alert alert-danger py-2 mb-0"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>
        <form method="POST" action="<?= $u ?>/authentication/reset-password.php">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
          <div class="mb-3">
            <label class="form-label" for="password">New Password</label>
            <input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="confirm-password">Confirm New Password</label>
            <input class="form-control" id="confirm-password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Update Password</button>
        </form>
        <?php endif; ?>
        <?php if ($error): ?>
        <a class="text-center small" href="<?= $u ?>/authentication/reset-password.php">Request another reset</a>
        <?php endif; ?>
        <?php else: ?>
        <p class="text-body-secondary small text-center mb-0">
          Enter your registered email. If it belongs to an account, reset instructions will be sent.
        </p>
        <?php if ($error): ?>
        <div class="alert alert-danger py-2 mb-0"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($notice): ?>
        <div class="alert alert-info py-2 mb-0"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>
        <?php if ($mailErrorDetail !== ''
            && filter_var(app_config('MAIL_DEBUG', false), FILTER_VALIDATE_BOOLEAN)): ?>
        <div class="alert alert-warning py-2 mb-0">
          Mail error (mail_debug is enabled):
          <span class="d-block mt-1"><?= htmlspecialchars($mailErrorDetail, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="d-block mt-1 small">See assets/cache/mail.log and admin/mail-test.php for details.</span>
        </div>
        <?php endif; ?>
        <form method="POST" action="<?= $u ?>/authentication/reset-password.php">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="email">Email Address</label>
            <input class="form-control" id="email" name="email" type="email"
              placeholder="your@email.com" value="<?= htmlspecialchars(is_string($_POST['email'] ?? null) ? $_POST['email'] : '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Request Reset</button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$success): ?>
    <div class="text-center text-body-secondary small">
      <a href="<?= $u ?>/authentication/login.php?force=1">← Back to Login</a>
    </div>
    <?php endif; ?>
  </div>
</div>
<script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
</body>
</html>
