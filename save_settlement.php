<?php
// Shim to bridge legacy forms/api to new MVC controller
require_once 'includes/db.php';
require_once 'app/Controllers/SettlementController.php';

use App\Controllers\SettlementController;

$controller = new SettlementController();
$controller->save();
?>