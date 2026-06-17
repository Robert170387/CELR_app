<?php
require_once 'includes/db.php';

try {
    echo "Starting index optimization...<br>";

    // Indexes for 'trips' table
    $pdo->exec("CREATE INDEX idx_trips_vehicle ON trips(vehicle_id)");
    $pdo->exec("CREATE INDEX idx_trips_driver ON trips(driver_id)");
    $pdo->exec("CREATE INDEX idx_trips_status ON trips(status)");
    $pdo->exec("CREATE INDEX idx_trips_date_load ON trips(date_load)");
    echo "Indexes added to 'trips' table.<br>";

    // Indexes for 'expenses' table
    $pdo->exec("CREATE INDEX idx_expenses_trip ON expenses(trip_id)");
    $pdo->exec("CREATE INDEX idx_expenses_vehicle ON expenses(vehicle_id)");
    $pdo->exec("CREATE INDEX idx_expenses_category ON expenses(category)");
    $pdo->exec("CREATE INDEX idx_expenses_date ON expenses(date)");
    echo "Indexes added to 'expenses' table.<br>";

    echo "<b>Success: Index optimization complete.</b>";

} catch (PDOException $e) {
    echo "Error applying indexes: " . $e->getMessage();
}
?>