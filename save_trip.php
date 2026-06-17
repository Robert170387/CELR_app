<?php
// Shim to bridge legacy forms to new MVC controller
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// --- TEMPORARY DEBUG: Log what's happening ---
$debug_log = __DIR__ . '/logs/save_trip_debug.log';
$debug_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
    'csrf_session' => $_SESSION['csrf_token'] ?? 'NOT SET',
    'csrf_post' => $_POST['csrf_token'] ?? 'NOT SET',
    'csrf_match' => isset($_SESSION['csrf_token'], $_POST['csrf_token']) 
        ? (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']) ? 'MATCH' : 'MISMATCH') 
        : 'CANNOT CHECK',
    'trip_id' => $_POST['trip_id'] ?? 'NOT SET',
    'origin' => $_POST['origin'] ?? 'NOT SET',
    'post_keys' => array_keys($_POST),
];
@file_put_contents($debug_log, json_encode($debug_data, JSON_PRETTY_PRINT) . "\n---\n", FILE_APPEND);

require_once 'app/Controllers/TripController.php';

use App\Controllers\TripController;

// Execute Controller Action
$controller = new TripController();
$controller->save();
?>