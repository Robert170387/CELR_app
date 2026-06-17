<?php
/**
 * Database Connection
 * Uses centralized Database class while maintaining backward compatibility
 */

// Include the new Database class
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

// Configure database (in production, use environment variables)
Database::setConfig([
    'host' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'celr_app',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4'
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
