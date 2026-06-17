<?php
/**
 * Migration: Create fuel_vouchers table
 * Creates the table for fuel voucher management
 */
require_once __DIR__ . '/../includes/db.php';

echo "Creating fuel_vouchers table...\n";

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS fuel_vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT NOT NULL,
        trip_id INT NULL,
        driver_id INT NULL,
        date DATE NOT NULL,
        gallons DECIMAL(10,2) DEFAULT 0,
        price_per_gallon DECIMAL(10,2) DEFAULT 0,
        total_amount DECIMAL(10,2) DEFAULT 0,
        station_name VARCHAR(255) DEFAULT NULL,
        station_location VARCHAR(255) DEFAULT NULL,
        receipt_number VARCHAR(100) DEFAULT NULL,
        payment_method VARCHAR(50) DEFAULT 'Efectivo',
        invoice_required TINYINT(1) DEFAULT 0,
        invoice_number VARCHAR(100) DEFAULT NULL,
        odometer_reading INT DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_vehicle_id (vehicle_id),
        INDEX idx_trip_id (trip_id),
        INDEX idx_driver_id (driver_id),
        INDEX idx_date (date),
        
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
        FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL,
        FOREIGN KEY (driver_id) REFERENCES personnel(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "✓ Table fuel_vouchers created successfully.\n";

    echo "✓ Table fuel_vouchers created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
