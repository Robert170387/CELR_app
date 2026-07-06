<?php
/**
 * Database Connection
 * Uses centralized Database class while maintaining backward compatibility
 */

// Load .env file if it exists (local development)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (!getenv($key)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
            }
        }
    }
}

// Include the new Database class
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/config_security.php';

use App\Core\Database;

// Configure database (in production, use environment variables)
Database::setConfig([
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'dbname' => getenv('DB_NAME') ?: 'celr_app',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
    'ssl' => getenv('DB_SSL') ?: 'false',
    'ssl_ca' => getenv('DB_SSL_CA') ?: '',
]);

// Get PDO instance for backward compatibility
try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());

    if (isProduction()) {
        die("Error de conexión a la base de datos. Por favor intente más tarde.");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

if (!function_exists('getDB')) {
    /**
     * Legacy helper function to get PDO instance
     * @deprecated Use App\Core\Database::getInstance() instead
     */
    function getDB(): PDO
    {
        return Database::getInstance();
    }
}

if (!function_exists('dbQuery')) {
    /**
     * Legacy helper for simple queries with backward compatibility
     * @deprecated Use App\Core\Database::query() instead
     */
    function dbQuery(string $sql, array $params = []): PDOStatement
    {
        return Database::query($sql, $params);
    }
}

if (!function_exists('dbFetchOne')) {
    /**
     * Legacy helper to fetch single row
     * @deprecated Use App\Core\Database::fetchOne() instead
     */
    function dbFetchOne(string $sql, array $params = []): ?array
    {
        return Database::fetchOne($sql, $params);
    }
}

if (!function_exists('dbFetchAll')) {
    /**
     * Legacy helper to fetch all rows
     * @deprecated Use App\Core\Database::fetchAll() instead
     */
    function dbFetchAll(string $sql, array $params = []): array
    {
        return Database::fetchAll($sql, $params);
    }
}
