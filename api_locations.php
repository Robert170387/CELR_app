<?php
// Shim for API Locations
require_once 'includes/db.php'; // Required for shim usage if controller relies on global $pdo or init
require_once 'app/Controllers/ApiController.php';

use App\Controllers\ApiController;

$controller = new ApiController();
$controller->getLocations();
?>