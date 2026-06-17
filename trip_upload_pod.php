<?php
// trip_upload_pod.php
require_once 'app/Controllers/TripController.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

use App\Controllers\TripController;

$controller = new TripController();
$controller->uploadPod();
?>