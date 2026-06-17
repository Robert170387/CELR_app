<?php
// Shim for API Settlement Data
require_once 'includes/db.php';
require_once 'app/Controllers/ApiController.php';

use App\Controllers\ApiController;

$controller = new ApiController();
$controller->getSettlementData();
?>