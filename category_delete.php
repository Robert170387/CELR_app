<?php
// category_delete.php - Shim for CategoryController
require_once 'app/Controllers/CategoryController.php';
$controller = new \App\Controllers\CategoryController();
$controller->destroy();
?>