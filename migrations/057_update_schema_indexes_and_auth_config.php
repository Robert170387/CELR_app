<?php
// migrations/057_update_schema_indexes_and_auth_config.php
require_once 'includes/db.php';

try {
    echo "Updating schema for indexes, auth lock, and config check...\n";

    // 1. Add columns to 'users' table
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN failed_attempts INT DEFAULT 0 NOT NULL AFTER last_login");
        echo "Added 'failed_attempts' column to users.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "'failed_attempts' column already exists.\n";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN locked_until DATETIME DEFAULT NULL AFTER failed_attempts");
        echo "Added 'locked_until' column to users.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "'locked_until' column already exists.\n";
        } else {
            throw $e;
        }
    }

    // 2. Add indexes to 'expenses' table
    $indexesExpenses = [
        'idx_trip_id' => 'trip_id',
        'idx_vehicle_id' => 'vehicle_id',
        'idx_date' => 'date'
    ];
    foreach ($indexesExpenses as $idxName => $colName) {
        try {
            $pdo->exec("ALTER TABLE expenses ADD INDEX $idxName ($colName)");
            echo "Added index $idxName on expenses($colName).\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                echo "Index $idxName already exists on expenses.\n";
            } else {
                echo "Warning index $idxName: " . $e->getMessage() . "\n";
            }
        }
    }

    // 3. Add indexes to 'trips' table
    $indexesTrips = [
        'idx_status' => 'status',
        'idx_date_load' => 'date_load',
        'idx_client_id' => 'client_id'
    ];
    foreach ($indexesTrips as $idxName => $colName) {
        try {
            $pdo->exec("ALTER TABLE trips ADD INDEX $idxName ($colName)");
            echo "Added index $idxName on trips($colName).\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                echo "Index $idxName already exists on trips.\n";
            } else {
                echo "Warning index $idxName: " . $e->getMessage() . "\n";
            }
        }
    }

    // 4. Add indexes to 'audit_logs' table
    try {
        $pdo->exec("ALTER TABLE audit_logs ADD INDEX idx_entity (entity_type, entity_id)");
        echo "Added composite index idx_entity on audit_logs(entity_type, entity_id).\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "Index idx_entity already exists on audit_logs.\n";
        } else {
            echo "Warning index idx_entity: " . $e->getMessage() . "\n";
        }
    }

    try {
        $pdo->exec("ALTER TABLE audit_logs ADD INDEX idx_created_at (created_at)");
        echo "Added index idx_created_at on audit_logs(created_at).\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "Index idx_created_at already exists on audit_logs.\n";
        } else {
            echo "Warning index idx_created_at: " . $e->getMessage() . "\n";
        }
    }

    // 5. Check and seed config table
    $stmt = $pdo->query("SELECT COUNT(*) FROM config");
    $configCount = $stmt->fetchColumn();
    if ($configCount == 0) {
        $pdo->exec("INSERT INTO config (id, currency, base_country, currency_symbol, decimal_separator, thousands_separator, decimal_count, default_rete_fuente, default_rete_ica, ganancia_nacional_percent, ganancia_urbano_percent) 
                    VALUES (1, 'COP', 'Colombia', '$', ',', '.', 0, 1.00, 1.00, 12.00, 30.00)");
        echo "Seeded default config row into config table.\n";
    } else {
        echo "Config table is already seeded.\n";
    }

    echo "Migration 057 executed successfully.\n";

} catch (PDOException $e) {
    echo "Error updating schema in migration 057: " . $e->getMessage() . "\n";
    throw $e;
}
?>
