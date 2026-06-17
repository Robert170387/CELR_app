<?php
// save_personnel.php - Shim for PersonnelController
require_once 'app/Controllers/PersonnelController.php';
$controller = new \App\Controllers\PersonnelController();
$controller->store();
?>