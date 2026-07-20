<?php
if (!defined('DB_HOST')) {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (substr($value, 0, 1) === '"' && substr($value, -1) === '"') $value = substr($value, 1, -1);
            if (substr($value, 0, 1) === "'" && substr($value, -1) === "'") $value = substr($value, 1, -1);
            $_ENV[$key] = $value;
        }
    }
    define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root');
    define('DB_PASS', $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '');
    define('DB_NAME', $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'exa');
}
