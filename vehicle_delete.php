<?php
// vehicle_delete.php - Shim for VehicleController
require_once 'app/Controllers/VehicleController.php';
$controller = new \App\Controllers\VehicleController();
$controller->destroy();
?>