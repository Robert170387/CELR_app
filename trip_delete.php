<?php
// trip_delete.php - Shim for TripController
require_once 'app/Controllers/TripController.php';
$controller = new \App\Controllers\TripController();
$controller->destroy();
?>