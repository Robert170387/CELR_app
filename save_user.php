<?php
// save_user.php - Shim for UserController
require_once 'app/Controllers/UserController.php';
$controller = new \App\Controllers\UserController();
$controller->store();
?>