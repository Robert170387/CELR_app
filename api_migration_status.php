<?php
// Shim for Migration Status API endpoint
// This allows the frontend to call this file directly if needed, or we can route via index.php?api=migration_status later
// But given the previous shims pattern, let's stick to it for consistency.
require_once 'includes/db.php';
require_once 'includes/auth.php'; // Ensure session is started for role check
require_once 'app/Controllers/ApiController.php';

use App\Controllers\ApiController;

$controller = new ApiController();
$controller->getMigrationStatus();
?>