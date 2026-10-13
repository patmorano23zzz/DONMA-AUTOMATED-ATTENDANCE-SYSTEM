<?php
/**
 * Reads deployment values from the hosting environment or a private local file.
 * config/deployment.php is intentionally excluded from version control.
 */
function app_config(string $name, mixed $default = null): mixed
{
    static $deployment = null;
    if ($deployment === null) {
        $path = __DIR__ . '/deployment.php';
        $deployment = is_file($path) ? require $path : [];
        if (!is_array($deployment)) {
            throw new RuntimeException('config/deployment.php must return an array.');
        }
    }

    $environmentValue = getenv($name);
    if ($environmentValue !== false && $environmentValue !== '') {
        return $environmentValue;
    }

    return $deployment[strtolower($name)] ?? $default;
}
