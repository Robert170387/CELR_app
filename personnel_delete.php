<?php
// personnel_delete.php - Shim for PersonnelController
require_once 'app/Controllers/PersonnelController.php';
$controller = new \App\Controllers\PersonnelController();
$controller->destroy();
?>