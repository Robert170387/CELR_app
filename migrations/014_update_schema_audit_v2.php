<?php
require_once 'includes/db.php';

try {
    echo "Creating Audit Trail Table...<br>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action VARCHAR(50) NOT NULL,      -- 'CREATE', 'UPDATE', 'DELETE', 'LOGIN'
        entity_type VARCHAR(50) NOT NULL, -- 'TRIP', 'EXPENSE', 'SETTLEMENT', 'VEHICLE'
        entity_id INT,
        details TEXT,                    -- Human readable summary of change
        old_values JSON,                 -- Previous state before change
        new_values JSON,                 -- New state after change
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    echo "Table 'audit_logs' created successfully.<br>";

    // Indexes for fast searching
    $pdo->exec("CREATE INDEX idx_audit_entity ON audit_logs(entity_type, entity_id)");
    $pdo->exec("CREATE INDEX idx_audit_user ON audit_logs(user_id)");
    $pdo->exec("CREATE INDEX idx_audit_date ON audit_logs(created_at)");

    echo "Indexes added.<br>";
    echo "<b style='color:green;'>Success: Audit System is ready.</b>";

} catch (PDOException $e) {
    echo "<b style='color:red;'>Error creating audit tables:</b> " . $e->getMessage();
}
?>