<?php
// Shim for Vehicle Controller
require_once 'includes/db.php';
require_once 'app/Controllers/VehicleController.php';

use App\Controllers\VehicleController;

$controller = new VehicleController();
$controller->save();
?>