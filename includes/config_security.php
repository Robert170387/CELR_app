<?php
/**
 * Application Configuration
 * Define environment and security settings
 */

// Define application environment: 'development' or 'production'
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Security settings based on environment
if (APP_ENV === 'production') {
    // Production: Hide all errors
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    // Development: Show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Security headers
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.tailwindcss.com cdn.jsdelivr.net unpkg.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com unpkg.com; font-src 'self' fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self' cdn.jsdelivr.net unpkg.com;");
}

// Call security headers on every request
setSecurityHeaders();

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // 1 hour

/**
 * Custom error handler for production
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (APP_ENV === 'production') {
        // Log error but don't display
        error_log("Error [$errno] $errstr in $errfile on line $errline");
        return true; // Don't execute PHP internal error handler
    }
    return false; // Let PHP handle it in development
}

set_error_handler('customErrorHandler');

/**
 * Exception handler for production
 */
function customExceptionHandler($exception) {
    if (APP_ENV === 'production') {
        error_log("Uncaught Exception: " . $exception->getMessage());
        http_response_code(500);
        echo "Ha ocurrido un error interno. Por favor contacte al administrador.";
    } else {
        // In development, show full details
        echo "<h1>Exception: " . get_class($exception) . "</h1>";
        echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    }
}

set_exception_handler('customExceptionHandler');

/**
 * Helper function to check if in production
 */
function isProduction() {
    return APP_ENV === 'production';
}

/**
 * Helper function to check if in development
 */
function isDevelopment() {
    return APP_ENV === 'development';
}
