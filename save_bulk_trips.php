<?php
require_once 'app/Controllers/TripController.php';

use App\Controllers\TripController;

$controller = new TripController();
$controller->processBulkPaste();
