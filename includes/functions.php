<?php
require_once 'db.php';

/**
 * CSRF Token Generation and Validation
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCsrfToken')) {
    function validateCsrfToken()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                die('Error de seguridad: Token CSRF inválido. Por favor recargue la página.');
            }
        }
    }
}

if (!function_exists('getCsrfToken')) {
    function getCsrfToken()
    {
        return generateCsrfToken();
    }
}

/**
 * Validates existence of required fields in POST data.
 * @param array $fields Array of field names => type ('string', 'numeric', 'date')
 */
if (!function_exists('validatePOST')) {
    function validatePOST($fields)
    {
        $errors = [];
        foreach ($fields as $field => $type) {
            if (!isset($_POST[$field]) || (is_string($_POST[$field]) && trim($_POST[$field]) === '')) {
                $errors[] = "El campo '$field' es obligatorio.";
                continue;
            }

            $val = $_POST[$field];
            if ($type === 'numeric' && !is_numeric($val)) {
                $errors[] = "El campo '$field' debe ser un número.";
            } elseif ($type === 'date') {
                $d = DateTime::createFromFormat('Y-m-d', $val);
                if (!$d || $d->format('Y-m-d') !== $val) {
                    $errors[] = "El campo '$field' debe ser una fecha válida (AAAA-MM-DD).";
                }
            }
        }
        return $errors;
    }
}

/**
 * Validates that financial inputs are not negative.
 */
if (!function_exists('validateFinancials')) {
    function validateFinancials($fields)
    {
        $errors = [];
        foreach ($fields as $field) {
            if (isset($_POST[$field]) && is_numeric($_POST[$field]) && $_POST[$field] < 0) {
                $errors[] = "El valor de '$field' no puede ser negativo.";
            }
        }
        return $errors;
    }
}

/**
 * Recalculate financial fields for a specific trip based on current config and expenses.
 */
if (!function_exists('calculateTripFinancials')) {
    function calculateTripFinancials($tripId)
    {
        global $pdo;

        // 1. Fetch Trip Data
        $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
        $stmt->execute([$tripId]);
        $trip = $stmt->fetch();

        if (!$trip)
            return false;

        // 2. Fetch Config for defaults (if needed, though usually used during creation)
        $stmtConfig = $pdo->query("SELECT * FROM config LIMIT 1");
        $config = $stmtConfig->fetch();

        // 3. Fetch Expenses for this trip (only conductor-paid expenses affect financials)
        // OPTIMIZED: Use JOIN instead of correlated subquery
        $stmtExpenses = $pdo->prepare("SELECT COALESCE(SUM(e.amount), 0) as total_expenses FROM expenses e WHERE e.trip_id = ? AND e.paid_by = 'Conductor'");
        $stmtExpenses->execute([$tripId]);
        $totalTripExpenses = $stmtExpenses->fetchColumn() ?: 0;

        // --- Calculations ---

        // A. Total Kms
        $kms_total = $trip['kms_end'] - $trip['kms_start'];

        // B. Adjusted Freight (Flete Liquidado por Peso)
        // Formula: (Flete Manifiesto / Peso Declarado) * Peso Destino
        $flete_liquidado = $trip['flete_bruto'];
        if ($trip['weight_declared'] > 0 && $trip['weight_dest'] > 0) {
            $flete_liquidado = ($trip['flete_bruto'] / $trip['weight_declared']) * $trip['weight_dest'];
        }

        // C. Recalculate Dynamic Deductibles (Sin IVA)
        $value_iva = 0; // IVA no se utiliza en los cálculos
        $value_rf = $flete_liquidado * ($trip['percent_rete_fuente'] / 100);
        $value_ica = $flete_liquidado * ($trip['percent_rete_ica'] / 100);
        $value_rete_iva = 0; // Rete IVA no se utiliza en los cálculos
        $value_d3 = $flete_liquidado * ($trip['percent_deductible_3'] / 100);

        // D. Deductibles Total (Sin IVA ni Rete IVA)
        $total_deductibles = $value_rf
            + $value_ica
            + $value_d3
            + $trip['value_deductible_4']
            + $trip['value_deductible_5']
            + $trip['value_deductible_6'];

        // E. Flete Neto (Lo que recibe el propietario después de todo)
        // Formula: Flete Liquidado - Total Deducibles (sin IVA)
        $flete_neto = $flete_liquidado - $total_deductibles;

        // F. Commission
        $commission_value = 0;
        $rate_used = 0;

        if ($trip['trip_type'] === 'nacional') {
            $rate_used = ($trip['commission_percent'] > 0) ? $trip['commission_percent'] : $config['ganancia_nacional_percent'];
            $commission_value = $flete_neto * ($rate_used / 100);

        } elseif ($trip['trip_type'] === 'urbano') {
            $rate_used = ($trip['commission_percent'] > 0) ? $trip['commission_percent'] : $config['ganancia_urbano_percent'];
            $base_for_commission = $flete_neto - $totalTripExpenses;
            if ($base_for_commission < 0)
                $base_for_commission = 0;
            $commission_value = $base_for_commission * ($rate_used / 100);
        }

        // G. Final Pay Expected (Dinero real a recibir en bancos)
        // Se resta el anticipo y TODOS los deducibles (sin IVA)
        $final_pay = $flete_liquidado - $trip['advance_manifest'] - $total_deductibles;

        // --- Update Trip ---
        $updateSql = "UPDATE trips SET 
                        kms_total = ?, 
                        total_deductibles = ?, 
                        value_rete_fuente = ?,
                        value_rete_ica = ?,
                        value_deductible_3 = ?,
                        flete_neto = ?, 
                        commission_value = ?, 
                        commission_percent = ?,
                        final_pay_expected = ? 
                      WHERE id = ?";

        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            $kms_total,
            $total_deductibles,
            $value_rf,
            $value_ica,
            $value_d3,
            $flete_neto,
            $commission_value,
            $rate_used,
            $final_pay,
            $tripId
        ]);

        return true;
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount)
    {
        global $pdo;
        static $cfg = null;
        if ($cfg === null) {
            $stmt = $pdo->query("SELECT currency_symbol, decimal_separator, thousands_separator, decimal_count FROM config LIMIT 1");
            $cfg = $stmt->fetch();
        }

        $symbol = $cfg['currency_symbol'] ?? '$';
        $dec_sep = $cfg['decimal_separator'] ?? ',';
        $thou_sep = $cfg['thousands_separator'] ?? '.';
        $dec_count = $cfg['decimal_count'] ?? 0;

        return $symbol . ' ' . number_format($amount, $dec_count, $dec_sep, $thou_sep);
    }
}

/**
 * Calculate the remaining balance for a driver (Advance - Expenses)
 */
if (!function_exists('calculateTripBalance')) {
    function calculateTripBalance($tripId)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT advance_owner, advance_manifest, advance_manifest_to_driver FROM trips WHERE id = ?");
        $stmt->execute([$tripId]);
        $trip = $stmt->fetch();

        $advance = $trip['advance_owner'] ?: 0;
        if ($trip['advance_manifest_to_driver']) {
            $advance += ($trip['advance_manifest'] ?: 0);
        }

        $stmtExp = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE trip_id = ? AND paid_by = 'Conductor'");
        $stmtExp->execute([$tripId]);
        $expenses = $stmtExp->fetchColumn() ?: 0;

        return $advance - $expenses;
    }
}

/**
 * Calculate the total accumulated balance for a driver across all PENDING trips.
 */
if (!function_exists('calculateDriverTotalPendingBalance')) {
    function calculateDriverTotalPendingBalance($driverId)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM trips WHERE driver_id = ? AND settlement_status = 'Pending'");
        $stmt->execute([$driverId]);
        $trips = $stmt->fetchAll();

        $totalBalance = 0;
        foreach ($trips as $t) {
            $tripBalance = calculateTripBalance($t['id']);
            $totalBalance += $tripBalance;
        }

        return $totalBalance;
    }
}

/**
 * Calculate the remaining balance for a driver (Advance - Expenses)
 */
if (!function_exists('calculateTripBalance')) {
    function calculateTripBalance($tripId)
    {
        global $pdo;
        // OPTIMIZED: Use JOIN instead of correlated subquery
        $stmt = $pdo->prepare("SELECT 
            COALESCE(t.advance_owner, 0) as advance_owner,
            COALESCE(t.advance_manifest, 0) as advance_manifest,
            COALESCE(t.advance_manifest_to_driver, 0) as advance_manifest_to_driver,
            COALESCE(SUM(e.amount), 0) as conductor_expenses
        FROM trips t
        LEFT JOIN expenses e ON t.id = e.trip_id AND e.paid_by = 'Conductor'
        WHERE t.id = ?
        GROUP BY t.id");
        $stmt->execute([$tripId]);
        $result = $stmt->fetch();

        $advance = $result['advance_owner'];
        if ($result['advance_manifest_to_driver']) {
            $advance += $result['advance_manifest'];
        }

        $expenses = $result['conductor_expenses'];

        return $advance - $expenses;
    }
}

/**
 * Calculate the total accumulated balance for a driver across all PENDING trips.
 */
if (!function_exists('calculateDriverTotalPendingBalance')) {
    function calculateDriverTotalPendingBalance($driverId)
    {
        global $pdo;
        // OPTIMIZED: Single query instead of N+1 queries
        $stmt = $pdo->prepare("SELECT 
            t.id,
            COALESCE(t.advance_owner, 0) as advance_owner,
            COALESCE(t.advance_manifest, 0) as advance_manifest,
            COALESCE(t.advance_manifest_to_driver, 0) as advance_manifest_to_driver,
            COALESCE(SUM(e.amount), 0) as conductor_expenses
        FROM trips t
        LEFT JOIN expenses e ON t.id = e.trip_id AND e.paid_by = 'Conductor'
        WHERE t.driver_id = ? AND t.settlement_status = 'Pending'
        GROUP BY t.id");
        $stmt->execute([$driverId]);
        $trips = $stmt->fetchAll();

        $totalBalance = 0;
        foreach ($trips as $t) {
            $advance = $t['advance_owner'];
            if ($t['advance_manifest_to_driver']) {
                $advance += $t['advance_manifest'];
            }
            $totalBalance += $advance - $t['conductor_expenses'];
        }

        return $totalBalance;
    }
}

/**
 * Check if a trip satisfies the settlement requirements to be closed
 */
if (!function_exists('isTripSettled')) {
    function isTripSettled($tripId)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT settlement_status FROM trips WHERE id = ?");
        $stmt->execute([$tripId]);
        $status = $stmt->fetchColumn();
        return $status === 'Complete';
    }
}

/**
 * Display standard Tailwind alerts based on GET parameters.
 */
if (!function_exists('displayAlerts')) {
    function displayAlerts()
    {
        if (isset($_GET['success'])) {
            $msg = match ($_GET['success']) {
                'deleted' => 'Registro eliminado correctamente.',
                'saved' => 'Cambios guardados con éxito.',
                default => 'Operación completada.'
            };
            echo '
            <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-green-700">' . $msg . '</p>
                    </div>
                </div>
            </div>';
        }

        if (isset($_GET['error'])) {
            $msg = match ($_GET['error']) {
                'fk_constraint' => 'No se puede eliminar: El registro tiene otros datos asociados.',
                'self_delete' => 'No puedes eliminar tu propia cuenta.',
                'not_found' => 'El registro no existe.',
                'date_logic' => 'Error de Fechas: La fecha de descargue no puede ser anterior a la de cargue.',
                'odometer_logic' => 'Error de Odómetro: El kilometraje final es menor al inicial y no se confirmó la inconsistencia.',
                default => 'Ocurrió un error inesperado al procesar la solicitud.'
            };
            echo '
            <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-red-700">' . $msg . '</p>
                    </div>
                </div>
            </div>';
        }
    }
}

/**
 * Get upcoming expiration alerts for Vehicles and Drivers.
 * Default warning threshold: 30 days.
 */
if (!function_exists('getUpcomingAlerts')) {
    function getUpcomingAlerts($days_threshold = null)
    {
        global $pdo;

        if ($days_threshold === null) {
            $stmtCfg = $pdo->query("SELECT doc_warning_days FROM config LIMIT 1");
            $days_threshold = $stmtCfg->fetchColumn() ?: 30;
        }

        $alerts = [];
        $today = date('Y-m-d');
        $warning_date = date('Y-m-d', strtotime("+$days_threshold days"));

        // 1. Vehicles: SOAT
        $stmt = $pdo->prepare("SELECT id, placa, expiry_soat FROM vehicles WHERE active=1 AND expiry_soat IS NOT NULL AND expiry_soat <= ? ORDER BY expiry_soat ASC");
        $stmt->execute([$warning_date]);
        while ($row = $stmt->fetch()) {
            $days = (strtotime($row['expiry_soat']) - strtotime($today)) / (60 * 60 * 24);
            $alerts[] = [
                'type' => 'Vehículo',
                'entity' => $row['placa'],
                'concept' => 'SOAT',
                'date' => $row['expiry_soat'],
                'days_left' => round($days),
                'link' => 'vehicle_form.php?id=' . $row['id']
            ];
        }

        // 2. Vehicles: Tecnomecánica
        $stmt = $pdo->prepare("SELECT id, placa, expiry_tecno FROM vehicles WHERE active=1 AND expiry_tecno IS NOT NULL AND expiry_tecno <= ? ORDER BY expiry_tecno ASC");
        $stmt->execute([$warning_date]);
        while ($row = $stmt->fetch()) {
            $days = (strtotime($row['expiry_tecno']) - strtotime($today)) / (60 * 60 * 24);
            $alerts[] = [
                'type' => 'Vehículo',
                'entity' => $row['placa'],
                'concept' => 'Tecno',
                'date' => $row['expiry_tecno'],
                'days_left' => round($days),
                'link' => 'vehicle_form.php?id=' . $row['id']
            ];
        }

        // 3. Vehicles: Policies
        $stmt = $pdo->prepare("SELECT id, placa, expiry_policy FROM vehicles WHERE active=1 AND expiry_policy IS NOT NULL AND expiry_policy <= ? ORDER BY expiry_policy ASC");
        $stmt->execute([$warning_date]);
        while ($row = $stmt->fetch()) {
            $days = (strtotime($row['expiry_policy']) - strtotime($today)) / (60 * 60 * 24);
            $alerts[] = [
                'type' => 'Vehículo',
                'entity' => $row['placa'],
                'concept' => 'Seguro',
                'date' => $row['expiry_policy'],
                'days_left' => round($days),
                'link' => 'vehicle_form.php?id=' . $row['id']
            ];
        }

        // 4. Personnel: License
        $stmt = $pdo->prepare("SELECT id, firstname, lastname, license_expiry FROM personnel WHERE active=1 AND license_expiry IS NOT NULL AND license_expiry <= ? ORDER BY license_expiry ASC");
        $stmt->execute([$warning_date]);
        while ($row = $stmt->fetch()) {
            $days = (strtotime($row['license_expiry']) - strtotime($today)) / (60 * 60 * 24);
            $alerts[] = [
                'type' => 'Conductor',
                'entity' => $row['firstname'] . ' ' . $row['lastname'],
                'concept' => 'Licencia',
                'date' => $row['license_expiry'],
                'days_left' => round($days),
                'link' => 'personnel_form.php?id=' . $row['id']
            ];
        }

        // Sort by days left (most urgent first)
        usort($alerts, function ($a, $b) {
            return $a['days_left'] <=> $b['days_left'];
        });

        return $alerts;
    }
}

/**
 * Get alerts for trips with pending collection older than 2 months.
 */
if (!function_exists('getOverdueCollectionAlerts')) {
    function getOverdueCollectionAlerts()
    {
        global $pdo;
        $alerts = [];
        $today = new DateTime();
        $twoMonthsAgo = (clone $today)->modify('-2 months');

        // OPTIMIZED: Use JOIN instead of correlated subquery
        $sql = "
            SELECT t.id, t.date_load, t.final_pay_expected,
                   COALESCE(SUM(tp.amount), 0) as paid_amount
            FROM trips t
            LEFT JOIN trip_payments tp ON t.id = tp.trip_id
            WHERE t.final_pay_expected > 0
            AND t.date_load <= ?
            GROUP BY t.id, t.date_load, t.final_pay_expected
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$twoMonthsAgo->format('Y-m-d')]);

        while ($row = $stmt->fetch()) {
            $balance = $row['final_pay_expected'] - $row['paid_amount'];
            if ($balance > 1000) {
                $loadDate = new DateTime($row['date_load']);
                $diff = $loadDate->diff($today);
                $daysPassed = $diff->days;

                $alerts[] = [
                    'type' => 'Cobro',
                    'entity' => 'ODT-' . $row['id'],
                    'concept' => 'Cartera +60 días',
                    'date' => $row['date_load'],
                    'days_left' => -$daysPassed,
                    'link' => 'trip_details.php?id=' . $row['id']
                ];
            }
        }
        return $alerts;
    }
}

/**
 * Fetches maintenance alerts based on mileage thresholds.
 */
if (!function_exists('getMaintenanceAlerts')) {
    function getMaintenanceAlerts()
    {
        global $pdo;

        $stmtCfg = $pdo->query("SELECT maint_warning_kms FROM config LIMIT 1");
        $global_warning = $stmtCfg->fetchColumn() ?: 500;

        $alerts = [];

        // Query using the optimized view for current mileage (last_kms)
        $sql = "SELECT ms.*, v.placa, va.last_kms
                FROM maintenance_schedules ms
                JOIN vehicles v ON ms.vehicle_id = v.id
                JOIN view_vehicle_availability va ON v.id = va.id
                WHERE ms.active = 1 
                  AND va.last_kms >= (ms.next_service_kms - IFNULL(ms.warning_margin_kms, $global_warning))
                ORDER BY ms.priority DESC, (ms.next_service_kms - va.last_kms) ASC";

        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch()) {
            $kms_left = $row['next_service_kms'] - $row['last_kms'];
            $is_overdue = $kms_left <= 0;

            $alerts[] = [
                'type' => 'maintenance',
                'entity' => $row['placa'],
                'concept' => $row['task_name'],
                'priority' => $row['priority'],
                'kms_left' => round($kms_left),
                'is_overdue' => $is_overdue,
                'link' => 'vehicle_details.php?id=' . $row['vehicle_id'] // Target for v2.0
            ];
        }

        return $alerts;
    }
}

if (!function_exists('renderWorkflowHeader')) {
    function renderWorkflowHeader($step, $trip_id = null)
    {
        if (!$trip_id)
            return;

        $steps = [
            1 => ['name' => 'General', 'url' => 'trip_create.php?edit=' . $trip_id],
            2 => ['name' => 'Anticipos', 'url' => 'trip_details.php?id=' . $trip_id],
            3 => ['name' => 'Combustible', 'url' => 'expense_form.php?category=combustible&trip_id=' . $trip_id],
            4 => ['name' => 'Gastos', 'url' => 'expense_form.php?trip_id=' . $trip_id]
        ];

        echo '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 mb-8">';
        echo '  <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">';
        echo '  <nav class="flex justify-center" aria-label="Progress">';
        echo '    <ol role="list" class="flex items-center space-x-5">';
        foreach ($steps as $num => $s) {
            $isActive = ($num == $step);
            $isComplete = ($num < $step);

            echo '<li class="flex items-center">';
            echo '  <a href="' . $s['url'] . '" class="group flex items-center">';
            if ($isComplete) {
                echo '  <div class="relative flex items-center justify-center w-6 h-6 bg-green-500 rounded-full group-hover:bg-green-600 transition">';
                echo '    <svg class="w-4 h-4 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
                echo '  </div>';
            } elseif ($isActive) {
                echo '  <div class="relative flex items-center justify-center w-6 h-6 border-2 border-brand-600 rounded-full ring-2 ring-brand-100" aria-current="step">';
                echo '    <span class="h-2 w-2 bg-brand-600 rounded-full"></span>';
                echo '  </div>';
            } else {
                echo '  <div class="relative flex items-center justify-center w-6 h-6 border-2 border-gray-200 rounded-full group-hover:border-gray-400 transition">';
                echo '    <span class="h-2 w-2 bg-transparent rounded-full"></span>';
                echo '  </div>';
            }
            echo '  <span class="ml-2 text-xs font-bold uppercase tracking-wider ' . ($isActive ? 'text-brand-600' : ($isComplete ? 'text-green-600' : 'text-gray-400 group-hover:text-gray-600')) . '">' . $s['name'] . '</span>';
            echo '  </a>';

            if ($num < count($steps)) {
                echo '<div class="ml-5 w-8 h-px bg-gray-200"></div>';
            }
            echo '</li>';
        }
        echo '    </ol>';
        echo '  </nav>';
        echo '  </div>';
        echo '</div>';
    }
}

/**
 * Logs a critical action to the audit_logs table.
 */
if (!function_exists('logAction')) {
    function logAction($action, $entityType, $entityId, $details = null, $oldValues = null, $newValues = null)
    {
        global $pdo;

        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $oldValJson = $oldValues ? json_encode($oldValues) : null;
        $newValJson = $newValues ? json_encode($newValues) : null;

        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, old_values, new_values, ip_address) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $entityType, $entityId, $details, $oldValJson, $newValJson, $ip]);
            return true;
        } catch (PDOException $e) {
            // Silently fail to not break the main app flow, but could be logged to a file
            return false;
        }
    }
}

/**
 * Logs a system-level error.
 */
if (!function_exists('logSystemError')) {
    function logSystemError($message)
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (action, entity_type, details, ip_address) VALUES ('ERROR', 'SYSTEM', ?, ?)");
            $stmt->execute([$message, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}

/**
 * Retrieves metrics for the system health widget.
 */
if (!function_exists('getSystemHealthMetrics')) {
    function getSystemHealthMetrics()
    {
        global $pdo;
        $metrics = [
            'recent_errors' => 0,
            'last_backup' => null
        ];

        try {
            // 1. Errors in last 24 hours
            $stmt = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE entity_type = 'SYSTEM' AND action = 'ERROR' AND created_at >= NOW() - INTERVAL 1 DAY");
            $metrics['recent_errors'] = $stmt->fetchColumn() ?: 0;

            // 2. Last Backup Date from Config
            $stmt = $pdo->query("SELECT last_backup_at FROM config LIMIT 1");
            $metrics['last_backup'] = $stmt->fetchColumn();

            // 3. Pending Migrations
            require_once __DIR__ . '/../app/Core/MigrationManager.php';
            $mm = new MigrationManager($pdo, __DIR__ . '/../migrations');
            $metrics['pending_migrations'] = $mm->getPendingMigrationsCount();

        } catch (PDOException $e) {
            // If even this fails, we have a major DB issue
        }

        return $metrics;
    }
}

if (!function_exists('setFlashMessage')) {
    function setFlashMessage($type, $title, $text) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_message'] = [
            'type' => $type,
            'title' => $title,
            'text' => $text
        ];
    }
}
?>