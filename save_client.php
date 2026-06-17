<?php
// save_client.php - Shim for ClientController
require_once 'app/Controllers/ClientController.php';
$controller = new \App\Controllers\ClientController();
$controller->store();
?>