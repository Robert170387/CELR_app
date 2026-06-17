<?php
// settlement_delete.php - Shim for SettlementController
require_once 'app/Controllers/SettlementController.php';
$controller = new \App\Controllers\SettlementController();
$controller->destroy();
?>