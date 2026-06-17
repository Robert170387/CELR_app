<?php
/**
 * CELR-App Comprehensive Test Suite 2025
 * Batería de Pruebas Integrales para validar la salud del sistema
 * 
 * Uso: php tests/test_runner.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Controllers/ExpenseController.php';
require_once __DIR__ . '/../app/Controllers/TripController.php';
require_once __DIR__ . '/../app/Helpers/Audit.php';

use App\Controllers\ExpenseController;
use App\Controllers\TripController;
use App\Helpers\Audit;

// Colores para CLI
if (php_sapi_name() === 'cli') {
    define('C_GRN', "\033[32m");
    define('C_RED', "\033[31m");
    define('C_YLW', "\033[33m");
    define('C_BLU', "\033[34m");
    define('C_RST', "\033[0m");
} else {
    define('C_GRN', '<span style="color:green">');
    define('C_RED', '<span style="color:red">');
    define('C_YLW', '<span style="color:orange">');
    define('C_BLU', '<span style="color:blue">');
    define('C_RST', '</span>');
}

class SuiteIntegral
{
    private $pdo;
    private $stats = ['passed' => 0, 'failed' => 0, 'total' => 0];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function run()
    {
        echo C_BLU . "==================================================" . C_RST . "\n";
        echo C_BLU . "   CELR-APP: BATERÍA DE PRUEBAS INTEGRALES 2025" . C_RST . "\n";
        echo C_BLU . "==================================================" . C_RST . "\n\n";

        $this->task1_Health();
        $this->task2_MVCFlow();
        $this->task3_Traceability();
        $this->task4_Performance();

        $this->printSummary();
    }

    private function task1_Health()
    {
        echo C_YLW . "[TAREA 1] Validación de Salud (db_diag.php)" . C_RST . "\n";

        // Simular ejecución de db_diag.php logicamente
        $this->assertTable('trips', ['origin_state_id', 'destination_state_id', 'origin_city_id', 'destination_city_id']);
        $this->assertTable('loc_cities', ['id', 'state_id', 'name']);

        // Verificar fuel_vouchers (puede ser tabla o columna)
        $hasTable = $this->pdo->query("SHOW TABLES LIKE 'fuel_vouchers'")->rowCount() > 0;
        $this->assertTrue($hasTable, "Tabla 'fuel_vouchers' existe");
    }

    private function task2_MVCFlow()
    {
        echo "\n" . C_YLW . "[TAREA 2] Test de Flujo de Datos MVC" . C_RST . "\n";

        // 1. Verificar Shims
        $this->assertTrue(file_exists(__DIR__ . '/../save_trip.php'), "Shim save_trip.php presente");
        $this->assertTrue(file_exists(__DIR__ . '/../save_expense.php'), "Shim save_expense.php presente");

        // 2. Simular inserción de Gasto via Controller
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        $_SESSION['csrf_token'] = 'test_token';

        // Buscamos un viaje activo para vincular
        $trip_id = $this->pdo->query("SELECT id FROM trips WHERE status='En Progreso' LIMIT 1")->fetchColumn();
        if (!$trip_id) {
            // Si no hay en progreso, cualquiera
            $trip_id = $this->pdo->query("SELECT id FROM trips LIMIT 1")->fetchColumn();
        }

        $_POST = [
            'id' => '',
            'csrf_token' => 'test_token',
            'category' => 'Peaje',
            'amount' => 15000,
            'date' => date('Y-m-d'),
            'paid_by' => 'Conductor',
            'vehicle_id' => 1,
            'trip_id' => $trip_id,
            'description' => 'TEST_INTEGRAL_FLOW'
        ];

        try {
            // Instanciar Controller Mockeando redirección
            $controller = new class extends ExpenseController {
                protected function redirect($url, $params = [])
                { /* No-op */
                }
            };

            $controller->store();

            // Verificar registro
            $expense = $this->pdo->query("SELECT * FROM expenses WHERE description='TEST_INTEGRAL_FLOW' ORDER BY id DESC LIMIT 1")->fetch();
            $this->assertTrue($expense !== false, "Gasto insertado correctamente vía MVC");
            $this->assertTrue($expense['trip_id'] == $trip_id, "Vínculo correcto con Viaje #$trip_id");

            // Limpiar
            if ($expense)
                $this->pdo->exec("DELETE FROM expenses WHERE id = " . $expense['id']);

        } catch (Exception $e) {
            $this->assertTrue(false, "Falla en flujo MVC: " . $e->getMessage());
        }

        // 3. Verificar route_profitability.php
        $this->assertTrue(file_exists(__DIR__ . '/../route_profitability.php'), "Archivo route_profitability.php existe");
    }

    private function task3_Traceability()
    {
        echo "\n" . C_YLW . "[TAREA 3] Prueba de Trazabilidad (Audit)" . C_RST . "\n";

        // Simular acción
        Audit::log('TEST_INTEGRAL', 'SYSTEM', 0, 'Verificación de trazabilidad 2025');

        $log = $this->pdo->query("SELECT * FROM audit_logs WHERE action='TEST_INTEGRAL' ORDER BY id DESC LIMIT 1")->fetch();
        $this->assertTrue($log !== false, "Audit::log escribió correctamente en la DB");
        $this->assertTrue(!empty($log['ip_address']), "IP capturada en el log (" . $log['ip_address'] . ")");
    }

    private function task4_Performance()
    {
        echo "\n" . C_YLW . "[TAREA 4] Prueba de Rendimiento de Ubicaciones" . C_RST . "\n";

        $start = microtime(true);
        // Simular lógica de api_locations.php para ciudades típicas (ej. Santander)
        $stmt = $this->pdo->prepare("SELECT ci.id, ci.name FROM loc_cities ci 
                               JOIN loc_states s ON ci.state_id = s.id 
                               JOIN loc_countries c ON s.country_id = c.id 
                               WHERE c.name = 'Colombia' AND s.name = 'Santander' LIMIT 100");
        $stmt->execute();
        $results = $stmt->fetchAll();
        $end = microtime(true);

        $ms = ($end - $start) * 1000;
        $this->assertTrue($ms < 500, "Carga de ciudades optimizada (" . round($ms, 2) . "ms)");

        // Verificar índices
        $explain = $this->pdo->query("EXPLAIN SELECT * FROM loc_cities WHERE state_id = 1")->fetch();
        $this->assertTrue($explain['key'] !== null, "Índice detectado en loc_cities (state_id)");
    }

    private function assertTable($table, $cols)
    {
        try {
            $exists = $this->pdo->query("DESCRIBE $table");
            $this->assertTrue(true, "Tabla '$table' existe");
            $existing = $exists->fetchAll(PDO::FETCH_COLUMN);
            foreach ($cols as $c) {
                $this->assertTrue(in_array($c, $existing), "Columna '$table.$c' existe");
            }
        } catch (Exception $e) {
            $this->assertTrue(false, "ERROR en tabla '$table': " . $e->getMessage());
        }
    }

    private function assertTrue($condition, $msg)
    {
        $this->stats['total']++;
        if ($condition) {
            $this->stats['passed']++;
            echo C_GRN . "  [PASS] " . C_RST . "$msg\n";
        } else {
            $this->stats['failed']++;
            echo C_RED . "  [FAIL] " . C_RST . "$msg\n";
        }
    }

    private function printSummary()
    {
        echo "\n" . C_BLU . "==================================================" . C_RST . "\n";
        echo "RESUMEN DE PRUEBAS:\n";
        echo "  Total: " . $this->stats['total'] . "\n";
        echo C_GRN . "  Pasaron: " . $this->stats['passed'] . C_RST . "\n";
        echo C_RED . "  Fallaron: " . $this->stats['failed'] . C_RST . "\n";
        echo C_BLU . "==================================================" . C_RST . "\n";

        if ($this->stats['failed'] === 0) {
            echo C_GRN . "RESULTADO GLOBAL: EXITOSO ✅" . C_RST . "\n";
        } else {
            echo C_RED . "RESULTADO GLOBAL: REQUIERE REVISIÓN ❌" . C_RST . "\n";
        }
    }
}

$suite = new SuiteIntegral($pdo);
$suite->run();
