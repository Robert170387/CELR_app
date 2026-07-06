<?php
// app/Controllers/ApiController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php'; // For auth check if needed
require_once __DIR__ . '/../../includes/security_utils.php';

use App\Core\Controller;
use PDO;
use PDOException;
use Exception;
use App\Helpers\Audit;

class ApiController extends Controller
{

    public function __construct()
    {
        parent::__construct();
        // Ensure JSON header for all API responses
        header('Content-Type: application/json');
    }

    /**
     * Handles Location API requests (Countries, States, Ciudades/Municipios)
     */
    public function getLocations()
    {
        if (!isLocalRequest()) {
            $this->requireAuth();
        }

        $action = $_GET['action'] ?? '';

        try {
            switch ($action) {
                case 'countries':
                    $stmt = $this->pdo->query("SELECT id, name, code as iso2 FROM loc_countries ORDER BY name ASC");
                    $this->jsonResponse(['error' => false, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                    break;

                case 'states':
                    $country = $_GET['country'] ?? '';
                    if (empty($country))
                        throw new Exception("Country is required");

                    $stmt = $this->pdo->prepare("SELECT s.id, s.name FROM loc_states s 
                                           JOIN loc_countries c ON s.country_id = c.id 
                                           WHERE c.name = ? ORDER BY s.name ASC");
                    $stmt->execute([$country]);
                    $this->jsonResponse(['error' => false, 'data' => ['states' => $stmt->fetchAll(PDO::FETCH_ASSOC)]]);
                    break;

                case 'cities':
                    $country = $_GET['country'] ?? '';
                    $state = $_GET['state'] ?? '';
                    if (empty($country) || empty($state))
                        throw new Exception("Country and State are required");

                    $stmt = $this->pdo->prepare("SELECT ci.id, ci.name FROM loc_cities ci 
                                           JOIN loc_states s ON ci.state_id = s.id 
                                           JOIN loc_countries c ON s.country_id = c.id 
                                           WHERE c.name = ? AND s.name = ? ORDER BY ci.name ASC");
                    $stmt->execute([$country, $state]);
                    $this->jsonResponse(['error' => false, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                    break;

                default:
                    $this->jsonResponse(['error' => true, 'message' => 'Invalid action'], 400);
                    break;
            }
        } catch (Exception $e) {
            $this->jsonResponse(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handles Trip Status Updates via AJAX
     */
    public function updateStatus()
    {
        $this->requireRole(['Admin', 'Staff']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (!is_array($data)) {
            $this->jsonResponse(['error' => 'JSON invalido'], 400);
        }

        $trip_id = $data['trip_id'] ?? null;
        $new_status = $data['status'] ?? null;
        $csrfToken = $data['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if (!$trip_id || !$new_status) {
            $this->jsonResponse(['error' => 'Faltan datos'], 400);
        }

        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            $this->jsonResponse(['error' => 'Token CSRF invalido'], 403);
        }

        $allowedStatuses = ['En Progreso', 'Entregado', 'Finalizado', 'Cancelado'];
        if (!in_array($new_status, $allowedStatuses, true)) {
            $this->jsonResponse(['error' => 'Estado invalido'], 422);
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE trips SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, (int) $trip_id]);
            $this->jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            error_log("Error updating trip status: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error al actualizar el estado'], 500);
        }
    }

    /**
     * Retrieves data for the Settlement Form
     */
    public function getSettlementData()
    {
        $this->requireRole(['Admin', 'Staff']);

        $personnel_id = $_GET['personnel_id'] ?? null;
        $date_start = $_GET['date_start'] ?? null;
        $date_end = $_GET['date_end'] ?? null;

        if (!$personnel_id || !$date_start || !$date_end) {
            $this->jsonResponse(['error' => 'Missing parameters'], 400);
        }

        try {
            // 1. Fetch Personnel Info
            $stmt = $this->pdo->prepare("SELECT firstname, lastname, document_number, bank_account, salary_basic, transport_assistance FROM personnel WHERE id = ?");
            $stmt->execute([$personnel_id]);
            $personnel = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$personnel) {
                $this->jsonResponse(['error' => 'Personnel not found'], 404);
            }

            // 2. Fetch Trips for the period
            $stmtTrips = $this->pdo->prepare("
                SELECT t.id, t.trip_type, t.commission_value, t.advance_manifest, t.advance_owner, t.advance_manifest_to_driver, 
                       t.origin, t.destination, t.manifest_number, t.manifest_date, t.date_load, t.flete_neto,
                       v.placa as vehicle_placa, mc.name as manifest_company_name,
                       CASE 
                           WHEN c.person_type = 'Jurídica' THEN c.business_name 
                           ELSE CONCAT(c.firstname, ' ', c.lastname1) 
                       END as client_name
                FROM trips t
                JOIN vehicles v ON t.vehicle_id = v.id
                LEFT JOIN clients c ON t.client_id = c.id
                LEFT JOIN manifest_companies mc ON t.manifest_company_id = mc.id
                WHERE t.driver_id = ? AND t.date_load BETWEEN ? AND ?
                ORDER BY t.date_load ASC
            ");
            $stmtTrips->execute([$personnel_id, $date_start, $date_end]);
            $trips = $stmtTrips->fetchAll(PDO::FETCH_ASSOC);

            // Calculations
            $totalCommissions = 0;
            $totalAdvances = 0;
            $totalAdvancesManifest = 0;
            $totalAdvancesOwner = 0;
            $totalExpenses = 0;
            $countNational = 0;
            $countUrban = 0;
            $tripsDetail = [];

            foreach ($trips as $t) {
                $totalCommissions += (float) $t['commission_value'];

                $advForOwner = (float) $t['advance_owner'];
                $advForManifest = $t['advance_manifest_to_driver'] ? (float) $t['advance_manifest'] : 0;

                $totalAdvancesOwner += $advForOwner;
                $totalAdvancesManifest += $advForManifest;
                $totalAdvances += ($advForOwner + $advForManifest);

                if ($t['trip_type'] === 'nacional')
                    $countNational++;
                if ($t['trip_type'] === 'urbano')
                    $countUrban++;

                // Expenses logic
                $stmtExp = $this->pdo->prepare("SELECT SUM(amount) FROM expenses WHERE trip_id = ? AND paid_by = 'Conductor'");
                $stmtExp->execute([$t['id']]);
                $tripExpenses = (float) ($stmtExp->fetchColumn() ?: 0);
                $totalExpenses += $tripExpenses;

                $tripsDetail[] = [
                    'id' => $t['id'],
                    'date_load' => $t['date_load'],
                    'manifest_date' => $t['manifest_date'],
                    'vehicle' => $t['vehicle_placa'],
                    'company' => $t['client_name'] ?: ($t['manifest_company_name'] ?: 'N/A'),
                    'manifest' => $t['manifest_number'],
                    'origin' => $t['origin'],
                    'destination' => $t['destination'],
                    'type' => $t['trip_type'],
                    'flete_neto' => (float) $t['flete_neto'],
                    'advance_manifest' => (float) $t['advance_manifest'],
                    'advance_owner' => (float) $t['advance_owner'],
                    'advance_manifest_to_driver' => (int) $t['advance_manifest_to_driver'],
                    'expenses' => $tripExpenses,
                    'commission' => (float) $t['commission_value']
                ];
            }

            $salaryBasic = (float) $personnel['salary_basic'];
            $transportAssistanceConfig = (float) $personnel['transport_assistance'];
            $effectiveTransportAssistance = ($salaryBasic + $totalCommissions) >= (2 * $salaryBasic) ? 0 : $transportAssistanceConfig;

            $balanceToDiscount = $totalAdvances - $totalExpenses;
            $grossTotal = $salaryBasic + $effectiveTransportAssistance + $totalCommissions;
            $netToPay = $grossTotal - $balanceToDiscount;

            $this->jsonResponse([
                'personnel' => $personnel,
                'summary' => [
                    'total_commissions' => $totalCommissions,
                    'total_advances' => $totalAdvances,
                    'total_advances_manifest' => $totalAdvancesManifest,
                    'total_advances_owner' => $totalAdvancesOwner,
                    'total_expenses' => $totalExpenses,
                    'balance_to_discount' => $balanceToDiscount,
                    'count_national' => $countNational,
                    'count_urban' => $countUrban,
                    'gross_total' => $grossTotal,
                    'transport_assistance_paid' => $effectiveTransportAssistance,
                    'net_to_pay' => $netToPay
                ],
                'trips' => $tripsDetail
            ]);

        } catch (PDOException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Retrieves Migration Status for Dashboard Widget
     */
    public function getMigrationStatus()
    {
        // 1. Security Check (Admin only)
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $this->jsonResponse(['error' => 'No autorizado'], 403);
        }

        try {
            require_once __DIR__ . '/../Core/MigrationManager.php';
            $migrationDir = __DIR__ . '/../../migrations';
            $mm = new \MigrationManager($this->pdo, $migrationDir);

            // 2. Migration Counts
            $files = scandir($migrationDir);
            $totalFiles = 0;
            $lastMigrationFile = '';

            foreach ($files as $file) {
                if ($file === '.' || $file === '..')
                    continue;
                $totalFiles++;
                $lastMigrationFile = $file; // Assumes sorted by name
            }

            $appliedMigrations = $mm->getAppliedMigrations();
            $appliedCount = count($appliedMigrations);
            $pendingCount = $totalFiles - $appliedCount;

            // 3. Diagnostic Checks
            $dbStatus = 'ok';
            try {
                $this->pdo->query("SELECT 1");
            } catch (PDOException $e) {
                $dbStatus = 'error';
            }

            // Check essential tables
            $essentialTables = ['users', 'trips', 'vehicles', 'settlements'];
            $missingTables = [];
            foreach ($essentialTables as $table) {
                try {
                    $result = $this->pdo->query("SHOW TABLES LIKE '$table'");
                    if ($result->rowCount() == 0) {
                        $missingTables[] = $table;
                    }
                } catch (PDOException $e) {
                    $missingTables[] = $table;
                }
            }

            // 4. Data Diagnostic (Formerly db_diag.php)
            $diagnostics = [
                'personnel' => $this->pdo->query("SELECT COUNT(*) FROM personnel")->fetchColumn(),
                'suppliers' => $this->pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn(),
                'users' => $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'trips' => $this->pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn()
            ];

            // 5. Schema Validation (Formerly check_schema.php)
            $schemaValidation = [];
            try {
                $stmt = $this->pdo->query("DESCRIBE trips");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $found_manifest_company = false;
                foreach ($columns as $col) {
                    if ($col['Field'] == 'manifest_company_id') {
                        $found_manifest_company = true;
                        break;
                    }
                }
                $schemaValidation['manifest_company_id_exists'] = $found_manifest_company;
            } catch (PDOException $e) {
                $schemaValidation['error'] = $e->getMessage();
            }

            // 6. Audit Log
            Audit::log('VIEW', 'SYSTEM_HEALTH', 0, "Nivel de salud chequeado. Pendientes: $pendingCount");

            $this->jsonResponse([
                'success' => true,
                'total_files' => $totalFiles,
                'applied_migrations' => $appliedCount,
                'pending_count' => $pendingCount,
                'status' => ($pendingCount === 0 && empty($missingTables) && $schemaValidation['manifest_company_id_exists']) ? 'success' : 'warning',
                'last_migration' => $lastMigrationFile,
                'db_connection' => $dbStatus,
                'missing_tables' => $missingTables,
                'diagnostics' => $diagnostics,
                'schema_validation' => $schemaValidation
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Retrieves Recent Audit Logs for Dashboard Widget
     */
    public function getRecentAuditLogs()
    {
        // 1. Security Check (Admin only)
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $this->jsonResponse(['error' => 'No autorizado'], 403);
        }

        try {
            $sql = "
                SELECT 
                    a.id, 
                    a.action, 
                    a.entity_type, 
                    a.created_at, 
                    a.details,
                    u.username,
                    u.full_name
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.action IN ('DELETE', 'UPDATE') 
                  AND a.entity_type IN ('TRIPS', 'SETTLEMENTS', 'USERS', 'VEHICLES')
                ORDER BY a.created_at DESC
                LIMIT 5
            ";

            $stmt = $this->pdo->query($sql);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format relative time (e.g., "5 min ago")
            foreach ($logs as &$log) {
                $time = strtotime($log['created_at']);
                $log['time_ago'] = $this->timeElapsedString($time);
                $log['actor'] = $log['full_name'] ?: $log['username'] ?: 'System';
            }

            $this->jsonResponse(['success' => true, 'logs' => $logs]);

        } catch (PDOException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function timeElapsedString($ptime)
    {
        $etime = time() - $ptime;
        if ($etime < 1)
            return 'just now';

        $a = [
            365 * 24 * 60 * 60 => 'año',
            30 * 24 * 60 * 60 => 'mes',
            24 * 60 * 60 => 'día',
            60 * 60 => 'hora',
            60 => 'minuto',
            1 => 'segundo'
        ];
        $a_plural = [
            'año' => 'años',
            'mes' => 'meses',
            'día' => 'días',
            'hora' => 'horas',
            'minuto' => 'minutos',
            'segundo' => 'segundos'
        ];

        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = round($d);
                return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str);
            }
        }
    }
    /**
     * Retrieves simulated GPS locations for vehicles
     */
    public function getVehicleLocations()
    {
        $this->requireRole(['Admin', 'Staff']);

        try {
            // Fetch active vehicles
            $stmt = $this->pdo->query("SELECT id, placa, brand, model FROM vehicles WHERE active = 1");
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $locations = [];
            // Bogotá coordinates as base
            $baseLat = 4.7110;
            $baseLng = -74.0721;

            foreach ($vehicles as $v) {
                // Random offset to simulate distribution
                // rand -50 to 50 / 1000 = +/- 0.05 degrees
                $latOffset = (mt_rand(-50, 50) / 1000);
                $lngOffset = (mt_rand(-50, 50) / 1000);

                $locations[] = [
                    'id' => $v['id'],
                    'placa' => $v['placa'],
                    'details' => $v['brand'] . ' ' . $v['model'],
                    'lat' => $baseLat + $latOffset,
                    'lng' => $baseLng + $lngOffset,
                    'status' => (mt_rand(0, 1) ? 'En Movimiento' : 'Detenido'),
                    'speed' => mt_rand(0, 90) . ' km/h',
                    'last_update' => date('Y-m-d H:i:s')
                ];
            }

            $this->jsonResponse(['success' => true, 'locations' => $locations]);

        } catch (PDOException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Triggers a database backup (Admin only)
     */
    public function triggerDatabaseBackup()
    {
        $this->requireRole('admin');

        try {
            $tables = [];
            $result = $this->pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $return = "-- CELR App Database Backup\n";
            $return .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";

            foreach ($tables as $table) {
                $quotedTable = '`' . str_replace('`', '``', $table) . '`';

                // Get create table
                $row2 = $this->pdo->query("SHOW CREATE TABLE $quotedTable")->fetch(PDO::FETCH_NUM);
                $return .= "DROP TABLE IF EXISTS $quotedTable;\n" . $row2[1] . ";\n\n";

                // Get data
                $result = $this->pdo->query("SELECT * FROM $quotedTable");
                $num_fields = $result->columnCount();

                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $return .= "INSERT INTO `$table` VALUES(";
                    for ($j = 0; $j < $num_fields; $j++) {
                        if (isset($row[$j])) {
                            // Escape values
                            $val = addslashes($row[$j]);
                            $val = str_replace("\n", "\\n", $val);
                            $return .= '"' . $val . '"';
                        } else {
                            $return .= 'NULL';
                        }
                        if ($j < ($num_fields - 1)) {
                            $return .= ',';
                        }
                    }
                    $return .= ");\n";
                }
                $return .= "\n\n";
            }

            $dir = 'uploads/backups/';
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $filename = 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $dir . $filename;
            file_put_contents($filepath, $return);

            // Update config
            $this->pdo->exec("UPDATE config SET last_backup_at = NOW()");

            Audit::log('BACKUP', 'SYSTEM', 0, "Respaldo generado exitosamente: $filename");

            $this->jsonResponse([
                'success' => true,
                'message' => 'Respaldo generado correctamente.',
                'filename' => $filename,
                'path' => $filepath,
                'at' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieves recent system alerts for the dashboard
     */
    public function getSystemAlerts()
    {
        $this->requireAuth();

        try {
            $stmt = $this->pdo->query("SELECT * FROM system_alerts WHERE is_read = 0 ORDER BY created_at DESC LIMIT 10");
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format time ago
            foreach ($alerts as &$a) {
                $a['time_ago'] = $this->timeElapsedString(strtotime($a['created_at']));
            }

            $this->jsonResponse(['success' => true, 'alerts' => $alerts]);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function downloadLatestBackup()
    {
        $this->requireRole('admin');

        $dir = 'uploads/backups/';
        if (!is_dir($dir)) {
            $this->jsonResponse(['success' => false, 'error' => 'Directorio de respaldos no encontrado.'], 404);
        }

        $files = glob($dir . 'db_backup_*.sql');
        if (empty($files)) {
            $this->jsonResponse(['success' => false, 'error' => 'No se encontraron archivos de respaldo.'], 404);
        }

        // Sort by modified time descending to get the latest
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $latestFile = $files[0];
        $filename = basename($latestFile);

        if (file_exists($latestFile)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($latestFile));
            readfile($latestFile);
            exit;
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'El archivo no existe en el servidor.'], 404);
        }
    }

    public function findActiveTrip()
    {
        $this->requireRole(['Admin', 'Staff']);

        $vehicle_id = $_GET['vehicle_id'] ?? null;
        $date = $_GET['date'] ?? date('Y-m-d');

        if (!$vehicle_id) {
            $this->jsonResponse(['error' => true, 'message' => 'Vehicle ID required']);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, origin, destination, date_load 
                FROM trips 
                WHERE vehicle_id = ? 
                AND status NOT IN ('Finalizado', 'Cancelado')
                AND ? >= date_load
                ORDER BY date_load DESC LIMIT 1
            ");
            $stmt->execute([$vehicle_id, $date]);
            $trip = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->jsonResponse(['error' => false, 'trip' => $trip]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    public function getTripFinancials()
    {
        $this->requireRole(['Admin', 'Staff']);

        $trip_id = $_GET['trip_id'] ?? null;
        if (!$trip_id || !ctype_digit((string) $trip_id)) {
            $this->jsonResponse(['error' => true, 'message' => 'Trip ID required'], 400);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, flete_neto, final_pay_expected, commission_value, total_deductibles
                FROM trips
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([(int) $trip_id]);
            $trip = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$trip) {
                $this->jsonResponse(['error' => true, 'message' => 'Trip not found'], 404);
            }

            $stmtExpenses = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount), 0)
                FROM expenses
                WHERE trip_id = ? AND paid_by = 'Conductor'
            ");
            $stmtExpenses->execute([(int) $trip_id]);
            $trip['total_conductor_expenses'] = (float) $stmtExpenses->fetchColumn();

            $this->jsonResponse(['error' => false, 'trip' => $trip]);
        } catch (PDOException $e) {
            error_log("Error fetching trip financials: " . $e->getMessage());
            $this->jsonResponse(['error' => true, 'message' => 'Error al consultar el viaje'], 500);
        }
    }
}
?>
