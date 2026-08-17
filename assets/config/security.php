<?php
/**
 * Security helpers — rate limiting, CSRF, session hardening
 * Include after session_start() on any page that needs it.
 */

// ── Session hardening ─────────────────────────────────────────────────────
function secure_session(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Regenerate session ID periodically (every 20 min)
    if (!isset($_SESSION['_last_regen'])) {
        $_SESSION['_last_regen'] = time();
    } elseif (time() - $_SESSION['_last_regen'] > 1200) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }

    // Session timeout — 2 hours of inactivity logs out
    $timeout = 7200;
    if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        session_start();
        return;
    }
    $_SESSION['_last_activity'] = time();
}

// ── CSRF ──────────────────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}

// ── Rate limiter (file-based, no Redis needed) ────────────────────────────
/**
 * @param string $key   Unique action key, e.g. 'login_192.168.1.1'
 * @param int    $limit Max attempts allowed in the window
 * @param int    $window Time window in seconds
 * @return bool  true = allowed, false = rate limited
 */
function rate_limit(string $key, int $limit = 10, int $window = 300): bool {
    $dir = __DIR__ . '/../assets/cache/rl';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $file = $dir . '/' . md5($key) . '.json';
    $now  = time();
    $data = ['count' => 0, 'window_start' => $now];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: $data;
        // Reset window if expired
        if (($now - $data['window_start']) >= $window) {
            $data = ['count' => 0, 'window_start' => $now];
        }
    }

    $data['count']++;
    file_put_contents($file, json_encode($data), LOCK_EX);

    return $data['count'] <= $limit;
}

/**
 * Returns seconds until rate limit resets, or 0 if not limited.
 */
function rate_limit_retry_after(string $key, int $window = 300): int {
    $dir  = __DIR__ . '/../assets/cache/rl';
    $file = $dir . '/' . md5($key) . '.json';
    if (!file_exists($file)) return 0;
    $data = json_decode(file_get_contents($file), true);
    $wait = $window - (time() - ($data['window_start'] ?? 0));
    return max(0, (int)$wait);
}
