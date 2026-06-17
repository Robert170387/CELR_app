<?php
// api_audit_logs.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'app/Controllers/ApiController.php';

use App\Controllers\ApiController;

$controller = new ApiController();
$controller->getRecentAuditLogs();
?>