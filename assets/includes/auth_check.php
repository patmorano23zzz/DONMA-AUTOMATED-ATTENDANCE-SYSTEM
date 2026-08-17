<?php
// Include this at the top of every protected page
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ' . (dirname($_SERVER['SCRIPT_NAME']) === '/' ? '' : '../') . '../authentication/login.php');
    exit;
}

// Session timeout — 2 hours of inactivity
$timeout = 7200;
if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    header('Location: ' . (dirname($_SERVER['SCRIPT_NAME']) === '/' ? '' : '../') . '../authentication/login.php?timeout=1');
    exit;
}
$_SESSION['_last_activity'] = time();

// Periodic session ID regeneration (every 20 minutes)
if (!isset($_SESSION['_last_regen'])) {
    $_SESSION['_last_regen'] = time();
} elseif (time() - $_SESSION['_last_regen'] > 1200) {
    session_regenerate_id(true);
    $_SESSION['_last_regen'] = time();
}
