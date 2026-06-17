<?php
// Shim for API Update Status
require_once 'includes/db.php';
require_once 'app/Controllers/ApiController.php';

use App\Controllers\ApiController;

$controller = new ApiController();
$controller->updateStatus();
?>