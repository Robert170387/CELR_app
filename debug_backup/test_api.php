<?php
require_once __DIR__ . '/includes/security_utils.php';
requireInternalToolAccess();

$_GET['action'] = 'cities';
$_GET['country'] = 'Colombia';
$_GET['state'] = 'Antioquia';
require_once 'api_locations.php';
?>
