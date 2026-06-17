<?php
/**
 * SHIM: backup_db.php -> ApiController@triggerDatabaseBackup
 * Standardizes database backup through MVC architecture.
 */
require_once 'app/Controllers/ApiController.php';
$controller = new \App\Controllers\ApiController();
$controller->triggerDatabaseBackup();
