<?php
require_once __DIR__ . '/config_security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isAuthenticated()
{
    return isset($_SESSION['user_id']);
}

// Get current user role
function getCurrentRole()
{
    return $_SESSION['role'] ?? null;
}

// Require a specific role to access the page
function requireRole($requiredRole)
{
    if (!isAuthenticated()) {
        header("Location: login.php");
        exit;
    }

    $currentRole = getCurrentRole();

    // Support array or string
    $roles = is_array($requiredRole) ? $requiredRole : [$requiredRole];

    // Role hierarchy logic
    if ($currentRole === 'admin')
        return true;

    if (!in_array($currentRole, $roles)) {
        header("Location: index.php?error=access_denied");
        exit;
    }
}

// Redirect helper
function redirect($url)
{
    header("Location: $url");
    exit;
}
?>