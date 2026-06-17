<?php
// migrate.php
require_once 'includes/db.php';
require_once 'app/Core/MigrationManager.php';

// Prepare environment
header('Content-Type: text/plain');

$manager = new MigrationManager($pdo, __DIR__ . '/migrations');
$manager->migrate();
?>