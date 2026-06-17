<?php
require_once 'includes/db.php';

try {
    echo "Creating Maintenance Module Tables...<br>";

    // 1. Maintenance Schedules Table
    // Defines recurring tasks and their thresholds
    $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance_schedules (
        id INT PRIMARY KEY AUTO_INCREMENT,
        vehicle_id INT NOT NULL,
        task_name VARCHAR(255) NOT NULL, -- e.g., 'Cambio de Aceite', 'Rotación de Llantas'
        interval_kms INT NOT NULL,      -- e.g., 5000, 10000
        last_service_kms DECIMAL(15,2) DEFAULT 0,
        next_service_kms DECIMAL(15,2) DEFAULT 0,
        warning_margin_kms INT DEFAULT 500, -- Alert starts 500km before
        priority ENUM('Baja', 'Media', 'Alta', 'Crítica') DEFAULT 'Media',
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
    )");
    echo "Table 'maintenance_schedules' created.<br>";

    // 2. Maintenance Logs Table
    // History of performed maintenance
    $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        schedule_id INT NOT NULL,
        performed_date DATE NOT NULL,
        performed_at_kms DECIMAL(15,2) NOT NULL,
        cost DECIMAL(15,2) DEFAULT 0,
        technician VARCHAR(255),
        notes TEXT,
        receipt_photo VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (schedule_id) REFERENCES maintenance_schedules(id) ON DELETE CASCADE
    )");
    echo "Table 'maintenance_logs' created.<br>";

    // Indexes for performance
    $pdo->exec("CREATE INDEX idx_maint_vehicle ON maintenance_schedules(vehicle_id)");
    $pdo->exec("CREATE INDEX idx_maint_next_kms ON maintenance_schedules(next_service_kms)");
    echo "Indexes added.<br>";

    echo "<b style='color:green;'>Success: Maintenance schema is ready.</b>";

} catch (PDOException $e) {
    echo "<b style='color:red;'>Error creating maintenance tables:</b> " . $e->getMessage();
}
?>