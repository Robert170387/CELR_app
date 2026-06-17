<?php
/**
 * Simple Cache System
 * Provides file-based and session-based caching
 */

namespace App\Core;

class Cache
{
    private static string $cacheDir = __DIR__ . '/../../cache/';
    private static int $defaultTTL = 3600; // 1 hour default
    
    /**
     * Initialize cache directory
     */
    public static function init(): void
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached data
     * 
     * @param string $key Cache key
     * @param int|null $ttl Time to live in seconds (null for default)
     * @return mixed|null Returns cached data or null if expired/not found
     */
    public static function get(string $key, ?int $ttl = null): mixed
    {
        self::init();
        $ttl = $ttl ?? self::$defaultTTL;
        
        // Try session cache first (faster)
        if (isset($_SESSION['cache'][$key])) {
            $cached = $_SESSION['cache'][$key];
            if ($cached['expires'] > time()) {
                return $cached['data'];
            }
            // Expired, remove from session
            unset($_SESSION['cache'][$key]);
        }
        
        // Try file cache
        $file = self::$cacheDir . md5($key) . '.cache';
        if (file_exists($file)) {
            $cached = unserialize(file_get_contents($file));
            if ($cached['expires'] > time()) {
                // Store in session for faster access next time
                $_SESSION['cache'][$key] = $cached;
                return $cached['data'];
            }
            // Expired, delete file
            unlink($file);
        }
        
        return null;
    }
    
    /**
     * Store data in cache
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public static function set(string $key, mixed $data, ?int $ttl = null): bool
    {
        self::init();
        $ttl = $ttl ?? self::$defaultTTL;
        
        $cached = [
            'expires' => time() + $ttl,
            'data' => $data
        ];
        
        // Store in session
        $_SESSION['cache'][$key] = $cached;
        
        // Store in file (persistent across requests)
        $file = self::$cacheDir . md5($key) . '.cache';
        return file_put_contents($file, serialize($cached)) !== false;
    }
    
    /**
     * Delete cached data
     * 
     * @param string $key Cache key
     * @return bool
     */
    public static function delete(string $key): bool
    {
        // Remove from session
        unset($_SESSION['cache'][$key]);
        
        // Remove file
        $file = self::$cacheDir . md5($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     * 
     * @return bool
     */
    public static function clear(): bool
    {
        // Clear session cache
        $_SESSION['cache'] = [];
        
        // Clear file cache
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Get or set cache (convenience method)
     * 
     * @param string $key Cache key
     * @param callable $callback Function to generate data if not cached
     * @param int|null $ttl Time to live
     * @return mixed
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cached = self::get($key, $ttl);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $data = $callback();
        self::set($key, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Cache database query results
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @param int|null $ttl Cache TTL
     * @return mixed
     */
    public static function rememberQuery(string $sql, array $params = [], ?int $ttl = null): mixed
    {
        $key = 'query_' . md5($sql . serialize($params));
        
        return self::remember($key, function() use ($sql, $params) {
            return Database::query($sql, $params)->fetchAll();
        }, $ttl);
    }
    
    /**
     * Invalidate cache by pattern (delete keys matching pattern)
     * 
     * @param string $pattern Pattern to match (e.g., 'config*')
     * @return int Number of deleted keys
     */
    public static function invalidate(string $pattern): int
    {
        $count = 0;
        
        // Check session cache
        if (isset($_SESSION['cache'])) {
            foreach ($_SESSION['cache'] as $key => $value) {
                if (fnmatch($pattern, $key)) {
                    unset($_SESSION['cache'][$key]);
                    $count++;
                }
            }
        }
        
        // Check file cache
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            $key = null;
            // We need to read the file to get the original key
            // For now, we'll just clear all if pattern is '*'
            if ($pattern === '*') {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Check if key exists in cache
     * 
     * @param string $key Cache key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }
}

/**
 * Helper function for quick cache access
 */
function cache_get(string $key, ?int $ttl = null): mixed
{
    return Cache::get($key, $ttl);
}

/**
 * Helper function for quick cache set
 */
function cache_set(string $key, mixed $data, ?int $ttl = null): bool
{
    return Cache::set($key, $data, $ttl);
}

/**
 * Helper function for cache remember
 */
function cache_remember(string $key, callable $callback, ?int $ttl = null): mixed
{
    return Cache::remember($key, $callback, $ttl);
}
