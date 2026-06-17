<?php
// supplier_delete.php - Shim for SupplierController
require_once 'app/Controllers/SupplierController.php';
$controller = new \App\Controllers\SupplierController();
$controller->destroy();
?>