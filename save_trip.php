<?php
// Shim to bridge legacy forms to new MVC controller
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_once 'app/Controllers/TripController.php';

use App\Controllers\TripController;

// Execute Controller Action
$controller = new TripController();
$controller->save();
?>
