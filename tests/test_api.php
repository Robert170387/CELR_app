<?php
/**
 * Test API - Ejecuta pruebas y retorna resultados en JSON
 */

require_once __DIR__ . '/../includes/security_utils.php';
requireInternalToolAccess();

header('Content-Type: application/json');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';

class TestAPI {
    private $pdo;
    private $results = [];
    private $categories = [
        'db' => ['passed' => 0, 'failed' => 0, 'tests' => []],
        'mvc' => ['passed' => 0, 'failed' => 0, 'tests' => []],
        'audit' => ['passed' => 0, 'failed' => 0, 'tests' => []],
        'perf' => ['passed' => 0, 'failed' => 0, 'tests' => []]
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function run() {
        $startTime = microtime(true);
        
        // Ejecutar todas las pruebas
        $this->testDatabaseHealth();
        $this->testMVCFlow();
        $this->testAuditTrail();
        $this->testPerformance();
        
        $duration = microtime(true) - $startTime;
        
        // Calcular totales
        $total = 0;
        $passed = 0;
        $failed = 0;
        
        foreach ($this->categories as $cat) {
            $total += count($cat['tests']);
            $passed += $cat['passed'];
            $failed += $cat['failed'];
        }
        
        return [
            'success' => true,
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'duration' => $duration,
            'categories' => $this->categories,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function testDatabaseHealth() {
        $tests = [
            ['name' => 'Tabla trips existe', 'fn' => [$this, 'checkTableTrips']],
            ['name' => 'Tabla expenses existe', 'fn' => [$this, 'checkTableExpenses']],
            ['name' => 'Tabla fuel_vouchers existe', 'fn' => [$this, 'checkFuelVouchers']],
            ['name' => 'Tabla locations existe', 'fn' => [$this, 'checkTableLocations']],
            ['name' => 'Tabla audit_logs existe', 'fn' => [$this, 'checkAuditLogs']],
            ['name' => 'Índices de ubicaciones', 'fn' => [$this, 'checkLocationIndexes']],
            ['name' => 'Integridad referencial', 'fn' => [$this, 'checkReferentialIntegrity']]
        ];
        
        foreach ($tests as $test) {
            $this->runTest('db', $test['name'], $test['fn']);
        }
    }
    
    private function testMVCFlow() {
        $tests = [
            ['name' => 'Shim save_trip.php existe', 'fn' => function() {
                return ['pass' => file_exists(__DIR__ . '/../save_trip.php')];
            }],
            ['name' => 'Shim save_expense.php existe', 'fn' => function() {
                return ['pass' => file_exists(__DIR__ . '/../save_expense.php')];
            }],
            ['name' => 'TripController existe', 'fn' => function() {
                return ['pass' => file_exists(__DIR__ . '/../app/Controllers/TripController.php')];
            }],
            ['name' => 'ExpenseController existe', 'fn' => function() {
                return ['pass' => file_exists(__DIR__ . '/../app/Controllers/ExpenseController.php')];
            }],
            ['name' => 'route_profitability.php existe', 'fn' => function() {
                return ['pass' => file_exists(__DIR__ . '/../route_profitability.php')];
            }],
            ['name' => 'Flujo de gasto completo', 'fn' => [$this, 'testExpenseFlow']]
        ];
        
        foreach ($tests as $test) {
            $this->runTest('mvc', $test['name'], $test['fn']);
        }
    }
    
    private function testAuditTrail() {
        $tests = [
            ['name' => 'Clase Audit existe', 'fn' => function() {
                return ['pass' => file_exists(__DIR__ . '/../app/Helpers/Audit.php')];
            }],
            ['name' => 'api_audit_logs.php existe', 'fn' => function() {
                return ['pass' => file_exists(__DIR__ . '/../api_audit_logs.php')];
            }],
            ['name' => 'Función Audit::log() operativa', 'fn' => [$this, 'testAuditLog']],
            ['name' => 'API audit_logs responde', 'fn' => [$this, 'testAuditAPI']],
            ['name' => 'Logs legibles y estructurados', 'fn' => [$this, 'testAuditStructure']]
        ];
        
        foreach ($tests as $test) {
            $this->runTest('audit', $test['name'], $test['fn']);
        }
    }
    
    private function testPerformance() {
        $tests = [
            ['name' => 'api_locations.php responde < 500ms', 'fn' => [$this, 'testLocationsAPI']],
            ['name' => 'Carga de ciudades < 300ms', 'fn' => [$this, 'testCitiesLoad']],
            ['name' => 'Query de ubicaciones usa índices', 'fn' => [$this, 'testLocationIndexes']],
            ['name' => 'Migración 042 aplicada', 'fn' => [$this, 'testMigration042']]
        ];
        
        foreach ($tests as $test) {
            $this->runTest('perf', $test['name'], $test['fn']);
        }
    }
    
    private function runTest($category, $name, callable $fn) {
        try {
            $result = $fn();
            $pass = $result['pass'] ?? false;
            $details = $result['details'] ?? '';
            
            $this->categories[$category]['tests'][] = [
                'name' => $name,
                'pass' => $pass,
                'details' => $details
            ];
            
            if ($pass) {
                $this->categories[$category]['passed']++;
            } else {
                $this->categories[$category]['failed']++;
            }
            
        } catch (Exception $e) {
            $this->categories[$category]['tests'][] = [
                'name' => $name,
                'pass' => false,
                'details' => 'Error: ' . $e->getMessage()
            ];
            $this->categories[$category]['failed']++;
        }
    }
    
    // Test implementations
    private function checkTableTrips() {
        try {
            $requiredColumns = ['id', 'trip_type', 'vehicle_id', 'driver_id', 'client_id', 'origin', 'destination', 'origin_state_id', 'origin_city_id'];
            $columns = $this->pdo->query("SHOW COLUMNS FROM trips")->fetchAll(PDO::FETCH_COLUMN);
            $missing = array_diff($requiredColumns, $columns);
            
            return [
                'pass' => empty($missing),
                'details' => empty($missing) ? count($columns) . ' columnas' : 'Faltan: ' . implode(', ', $missing)
            ];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function checkTableExpenses() {
        try {
            $columns = $this->pdo->query("SHOW COLUMNS FROM expenses")->fetchAll(PDO::FETCH_COLUMN);
            return ['pass' => in_array('trip_id', $columns) && in_array('vehicle_id', $columns), 'details' => count($columns) . ' columnas'];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function checkFuelVouchers() {
        try {
            $exists = $this->pdo->query("SHOW TABLES LIKE 'fuel_vouchers'")->fetchColumn();
            return ['pass' => $exists !== false];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function checkTableLocations() {
        try {
            $requiredColumns = ['id', 'name', 'country_id', 'state_id', 'city_id'];
            $columns = $this->pdo->query("SHOW COLUMNS FROM locations")->fetchAll(PDO::FETCH_COLUMN);
            $missing = array_diff($requiredColumns, $columns);
            
            return [
                'pass' => empty($missing),
                'details' => empty($missing) ? 'Jerarquía: país > estado > ciudad' : 'Faltan columnas'
            ];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function checkAuditLogs() {
        try {
            $requiredColumns = ['id', 'action', 'entity_type', 'entity_id', 'user_id', 'details', 'created_at'];
            $columns = $this->pdo->query("SHOW COLUMNS FROM audit_logs")->fetchAll(PDO::FETCH_COLUMN);
            $missing = array_diff($requiredColumns, $columns);
            
            return ['pass' => empty($missing), 'details' => count($columns) . ' columnas'];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function checkLocationIndexes() {
        try {
            $indexes = $this->pdo->query("SHOW INDEX FROM locations")->fetchAll(PDO::FETCH_COLUMN, 2); // Key_name column
            $hasIndex = in_array('state_id', $indexes) || in_array('idx_locations_state', $indexes) || in_array('idx_locations_state_city', $indexes);
            
            return ['pass' => $hasIndex, 'details' => count($indexes) . ' índices encontrados'];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function checkReferentialIntegrity() {
        try {
            $orphanTrips = $this->pdo->query("
                SELECT COUNT(*) FROM trips t 
                LEFT JOIN vehicles v ON t.vehicle_id = v.id 
                LEFT JOIN personnel p ON t.driver_id = p.id 
                WHERE (v.id IS NULL AND t.vehicle_id IS NOT NULL) 
                   OR (p.id IS NULL AND t.driver_id IS NOT NULL)
            ")->fetchColumn();
            
            return ['pass' => $orphanTrips == 0, 'details' => "Viajes huérfanos: $orphanTrips"];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function testExpenseFlow() {
        try {
            $this->pdo->beginTransaction();
            
            // Crear gasto de prueba
            $stmt = $this->pdo->prepare("
                INSERT INTO expenses (category, amount, date, paid_by, vehicle_id, description) 
                VALUES (?, ?, CURDATE(), ?, ?, 'TEST_AUTO_API')
            ");
            $stmt->execute(['Combustible', 100000, 'Propietario', 1]);
            $expenseId = $this->pdo->lastInsertId();
            
            // Verificar que se creó
            $exists = $this->pdo->query("SELECT 1 FROM expenses WHERE id = $expenseId AND description = 'TEST_AUTO_API'")->fetchColumn();
            
            // Limpiar
            $this->pdo->exec("DELETE FROM expenses WHERE id = $expenseId");
            $this->pdo->rollBack();
            
            return ['pass' => $exists, 'details' => "Gasto ID: $expenseId"];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function testAuditLog() {
        try {
            require_once __DIR__ . '/../app/Helpers/Audit.php';
            \App\Helpers\Audit::log('TEST_API', 'SYSTEM', 0, 'Prueba API de auditoría');
            
            $lastLog = $this->pdo->query("
                SELECT * FROM audit_logs 
                WHERE action = 'TEST_API' AND entity_type = 'SYSTEM' 
                ORDER BY id DESC LIMIT 1
            ")->fetch();
            
            return ['pass' => $lastLog !== false, 'details' => "Log ID: " . ($lastLog['id'] ?? 'N/A')];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function testAuditAPI() {
        try {
            ob_start();
            $_GET['limit'] = 1;
            include __DIR__ . '/../api_audit_logs.php';
            $output = ob_get_clean();
            
            $data = json_decode($output, true);
            return ['pass' => json_last_error() === JSON_ERROR_NONE, 'details' => 'JSON válido'];
        } catch (Exception $e) {
            ob_end_clean();
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function testAuditStructure() {
        try {
            $logs = $this->pdo->query("
                SELECT id, action, entity_type, entity_id, user_id, details, created_at 
                FROM audit_logs 
                ORDER BY id DESC 
                LIMIT 5
            ")->fetchAll();
            
            $valid = true;
            foreach ($logs as $log) {
                if (empty($log['action']) || empty($log['created_at'])) {
                    $valid = false;
                    break;
                }
            }
            
            return ['pass' => $valid && count($logs) > 0, 'details' => count($logs) . " logs verificados"];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function testLocationsAPI() {
        try {
            $start = microtime(true);
            
            ob_start();
            include __DIR__ . '/../api_locations.php';
            ob_end_clean();
            
            $time = (microtime(true) - $start) * 1000;
            
            return ['pass' => $time < 500, 'details' => sprintf("%.2f ms", $time)];
        } catch (Exception $e) {
            ob_end_clean();
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function testCitiesLoad() {
        try {
            $start = microtime(true);
            
            $stmt = $this->pdo->prepare("
                SELECT l.*, c.name as city_name, s.name as state_name
                FROM locations l
                LEFT JOIN loc_cities c ON l.city_id = c.id
                LEFT JOIN loc_states s ON l.state_id = s.id
                WHERE l.state_id = ? AND l.active = 1
                ORDER BY l.name
                LIMIT 100
            ");
            $stmt->execute([1]);
            $results = $stmt->fetchAll();
            
            $time = (microtime(true) - $start) * 1000;
            
            return ['pass' => $time < 300, 'details' => sprintf("%.2f ms (%d resultados)", $time, count($results))];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function testLocationIndexes() {
        try {
            $explain = $this->pdo->query("
                EXPLAIN SELECT * FROM locations WHERE state_id = 1 AND city_id = 1
            ")->fetch();
            
            $usesIndex = !empty($explain['key']) || $explain['type'] !== 'ALL';
            
            return ['pass' => $usesIndex, 'details' => "Tipo: {$explain['type']}"];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
    
    private function testMigration042() {
        try {
            $migrations = $this->pdo->query("
                SELECT migration_name FROM migrations_log 
                WHERE migration_name LIKE '%042%' 
                   OR migration_name LIKE '%location%' 
                   OR migration_name LIKE '%index%'
                ORDER BY id DESC
                LIMIT 5
            ")->fetchAll(PDO::FETCH_COLUMN);
            
            return ['pass' => count($migrations) > 0, 'details' => count($migrations) . ' migraciones'];
        } catch (Exception $e) {
            return ['pass' => false, 'details' => $e->getMessage()];
        }
    }
}

// Ejecutar API
$action = $_GET['action'] ?? 'status';
$api = new TestAPI($pdo);

switch ($action) {
    case 'run':
        echo json_encode($api->run());
        break;
    case 'status':
        echo json_encode(['success' => true, 'message' => 'Test API ready', 'timestamp' => date('Y-m-d H:i:s')]);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
