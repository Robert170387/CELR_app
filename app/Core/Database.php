<?php
/**
 * Database Class - Singleton Pattern
 * Centralizes database connection and provides helper methods
 */

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        // Prevent instantiation
    }
    
    /**
     * Get database connection instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::initialize();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize database connection
     */
    private static function initialize(): void
    {
        // Load configuration from environment or config file
        $host = self::$config['host'] ?? getenv('DB_HOST') ?: 'localhost';
        $dbname = self::$config['dbname'] ?? getenv('DB_NAME') ?: 'celr_app';
        $username = self::$config['username'] ?? getenv('DB_USER') ?: 'root';
        $password = self::$config['password'] ?? getenv('DB_PASS') ?: '';
        $charset = self::$config['charset'] ?? 'utf8mb4';
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_PERSISTENT => true
        ];
        
        try {
            self::$instance = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \Exception("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Set database configuration
     */
    public static function setConfig(array $config): void
    {
        self::$config = $config;
        // Reset instance to use new config
        self::$instance = null;
    }
    
    /**
     * Execute a prepared statement with parameters
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single column value
     */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    /**
     * Insert data and return last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        self::query($sql, array_values($data));
        
        return (int) self::getInstance()->lastInsertId();
    }
    
    /**
     * Update data
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        $stmt = self::query($sql, array_merge($values, $whereParams));
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete data
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = self::query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }
    
    /**
     * Check if in transaction
     */
    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }
    
    /**
     * Get last insert ID
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone()
    {
        // Prevent cloning
    }
    
    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
