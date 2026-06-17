<?php
require_once 'includes/db.php';

echo "Updating locations and trips tables for hierarchical location management...\n";

function addColumnIfNotExists($pdo, $table, $column, $definition)
{
    if (!$pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch()) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $column $definition");
        echo " - Added column $column to $table\n";
    }
}

try {
    // 1. Update 'locations' table
    addColumnIfNotExists($pdo, 'locations', 'country_id', 'INT NULL AFTER name');
    addColumnIfNotExists($pdo, 'locations', 'state_id', 'INT NULL AFTER country_id');
    addColumnIfNotExists($pdo, 'locations', 'city_id', 'INT NULL AFTER state_id');

    // Add foreign keys (ignore if they fail since they might already exist)
    try {
        $pdo->exec("ALTER TABLE locations ADD FOREIGN KEY (country_id) REFERENCES loc_countries(id)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE locations ADD FOREIGN KEY (state_id) REFERENCES loc_states(id)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE locations ADD FOREIGN KEY (city_id) REFERENCES loc_cities(id)");
    } catch (Exception $e) {
    }

    echo "Updated 'locations' table.\n";
} catch (PDOException $e) {
    echo "Locations error: " . $e->getMessage() . "\n";
}

try {
    // 2. Update 'trips' table
    addColumnIfNotExists($pdo, 'trips', 'origin_city_id', 'INT NULL AFTER origin');
    addColumnIfNotExists($pdo, 'trips', 'origin_state_id', 'INT NULL AFTER origin_city_id');
    addColumnIfNotExists($pdo, 'trips', 'destination_city_id', 'INT NULL AFTER destination');
    addColumnIfNotExists($pdo, 'trips', 'destination_state_id', 'INT NULL AFTER destination_city_id');

    // Add foreign keys
    try {
        $pdo->exec("ALTER TABLE trips ADD FOREIGN KEY (origin_city_id) REFERENCES loc_cities(id)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE trips ADD FOREIGN KEY (origin_state_id) REFERENCES loc_states(id)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE trips ADD FOREIGN KEY (destination_city_id) REFERENCES loc_cities(id)");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE trips ADD FOREIGN KEY (destination_state_id) REFERENCES loc_states(id)");
    } catch (Exception $e) {
    }

    echo "Updated 'trips' table.\n";
} catch (PDOException $e) {
    echo "Trips error: " . $e->getMessage() . "\n";
}

echo "Migration complete.\n";
?>