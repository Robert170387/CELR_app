<?php
// expense_delete.php - Shim for ExpenseController
require_once 'app/Controllers/ExpenseController.php';
$controller = new \App\Controllers\ExpenseController();
$controller->destroy();
?>