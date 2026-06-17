<?php
// client_delete.php - Shim for ClientController
require_once 'app/Controllers/ClientController.php';
$controller = new \App\Controllers\ClientController();
$controller->destroy();
?>