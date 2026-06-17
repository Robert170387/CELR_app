<?php
// Central API Router
require_once 'includes/db.php';
require_once 'app/Controllers/ApiController.php';

use App\Controllers\ApiController;

$action = $_GET['action'] ?? '';
$controller = new ApiController();

switch ($action) {
    case 'getLocations':
        $controller->getLocations();
        break;
    case 'updateStatus':
        $controller->updateStatus();
        break;
    case 'getSettlementData':
        $controller->getSettlementData();
        break;
    case 'getMigrationStatus':
        $controller->getMigrationStatus();
        break;
    case 'getRecentAuditLogs':
        $controller->getRecentAuditLogs();
        break;
    case 'getVehicleLocations':
        $controller->getVehicleLocations();
        break;
    case 'fetchSatrackLocations':
        $controller->fetchSatrackLocations();
        break;
    case 'triggerDatabaseBackup':
        $controller->triggerDatabaseBackup();
        break;
    case 'getSystemAlerts':
        $controller->getSystemAlerts();
        break;
    case 'downloadLatestBackup':
        $controller->downloadLatestBackup();
        break;
    case 'findActiveTrip':
        $controller->findActiveTrip();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action: ' . $action]);
        break;
}
?>