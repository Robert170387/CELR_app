<?php
require_once 'includes/db.php';

try {
    echo "Starting advanced index optimization...<br>";

    // Additional indexes for 'trips' table
    $pdo->exec("CREATE INDEX idx_trips_client ON trips(client_id)");
    $pdo->exec("CREATE INDEX idx_trips_settlement ON trips(settlement_status)");
    $pdo->exec("CREATE INDEX idx_trips_report_monthly ON trips(date_load, status)");
    echo "Advanced indexes added to 'trips' table.<br>";

    // Index for 'expenses' table paid_by
    $pdo->exec("CREATE INDEX idx_expenses_paid_by ON expenses(paid_by)");
    echo "Index added to 'expenses' table.<br>";

    // Indexes for personnel and clients active status
    $pdo->exec("CREATE INDEX idx_personnel_active ON personnel(active, type)");
    $pdo->exec("CREATE INDEX idx_clients_active ON clients(active)");
    echo "Status indexes added to personnel and clients.<br>";

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