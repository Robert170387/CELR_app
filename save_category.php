<?php
// save_category.php - Shim for CategoryController
require_once 'app/Controllers/CategoryController.php';
$controller = new \App\Controllers\CategoryController();
$controller->store();
?>