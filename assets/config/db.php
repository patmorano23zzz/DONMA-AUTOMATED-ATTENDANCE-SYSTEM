<?php
/**
 * Database Configuration & Connection
 * Don Marcelo C. Marty Elementary School — Attendance Tracking System
 */

// ── App base URL (no trailing slash) ─────────────────────────────────────────
// Detects http vs https and host automatically. Works on localhost and live domains.
if (!defined('APP_URL')) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Walk up from this file (config/) to find the app root relative to docroot
    $docRoot  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs'));
    $appRoot  = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $subPath  = str_replace($docRoot, '', $appRoot);
    define('APP_URL', $scheme . '://' . $host . $subPath);
}

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'donma_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed. Please try again later.']));
        }
    }
    return $pdo;
}
