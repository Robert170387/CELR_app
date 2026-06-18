<?php
require_once 'includes/db.php';

try {
    echo "Starting advanced index optimization...<br>";

    $indexes = [
        'trips'     => ['idx_trips_client' => 'client_id', 'idx_trips_settlement' => 'settlement_status', 'idx_trips_report_monthly' => 'date_load, status'],
        'expenses'  => ['idx_expenses_paid_by' => 'paid_by'],
        'personnel' => ['idx_personnel_active' => 'active, type'],
        'clients'   => ['idx_clients_active' => 'active'],
    ];
    foreach ($indexes as $table => $idxList) {
        $existing = $pdo->query("SHOW INDEX FROM $table")->fetchAll(PDO::FETCH_COLUMN, 2);
        foreach ($idxList as $name => $cols) {
            if (!in_array($name, $existing)) {
                $pdo->exec("CREATE INDEX $name ON $table($cols)");
            }
        }
    }
    echo "Advanced indexes checked/added.<br>";

    // Create View for Vehicle Availability
    $pdo->exec("DROP VIEW IF EXISTS view_vehicle_availability");
    $pdo->exec("
        CREATE VIEW view_vehicle_availability AS
        SELECT v.id, v.placa, 
               (SELECT t.kms_end FROM trips t WHERE t.vehicle_id = v.id ORDER BY t.date_load DESC, t.id DESC LIMIT 1) as last_kms,
               EXISTS(SELECT 1 FROM trips t WHERE t.vehicle_id = v.id AND t.status = 'En Progreso') as is_busy
        FROM vehicles v
    ");
    echo "Vehicle availability view created.<br>";

    echo "<b style='color:green;'>Success: Advanced optimization complete.</b>";

} catch (PDOException $e) {
    echo "<b style='color:red;'>Error applying optimizations:</b> " . $e->getMessage();
}
?>