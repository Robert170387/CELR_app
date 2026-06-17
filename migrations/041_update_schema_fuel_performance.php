<?php
require_once 'includes/db.php';

try {
    // Add kms_driven_on_fuel to help KPL/G calculation if needed, 
    // but we can calculate it from tank_mileage vs kms_start.

    // Check if we need more fields in expenses for the "FORMATO 2025"
    $pdo->exec("ALTER TABLE expenses ADD COLUMN IF NOT EXISTS invoice_status VARCHAR(50) DEFAULT 'Pendiente'");

    // Satrack integration already added satrack_id to vehicles, 
    // but let's ensure health metrics has what it needs.

    echo "Migration 041 completed: Schema optimized for Performance KPIs.<br>";

} catch (PDOException $e) {
    echo "Migration 041 Info: " . $e->getMessage() . "<br>";
}
