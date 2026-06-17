<?php
require_once 'includes/db.php';

try {
    echo "Starting location index optimization...<br>";

    // Indexes for 'loc_countries' table
    $pdo->exec("CREATE INDEX idx_loc_countries_name ON loc_countries(name)");
    echo "Index added to 'loc_countries(name)'.<br>";

    // Indexes for 'loc_states' table
    $pdo->exec("CREATE INDEX idx_loc_states_country_id ON loc_states(country_id)");
    $pdo->exec("CREATE INDEX idx_loc_states_name ON loc_states(name)");
    echo "Indexes added to 'loc_states(country_id, name)'.<br>";

    // Indexes for 'loc_cities' table
    $pdo->exec("CREATE INDEX idx_loc_cities_state_id ON loc_cities(state_id)");
    $pdo->exec("CREATE INDEX idx_loc_cities_name ON loc_cities(name)");
    echo "Indexes added to 'loc_cities(state_id, name)'.<br>";

    echo "<b>Success: Location index optimization complete.</b>";

} catch (PDOException $e) {
    echo "Error applying location indexes: " . $e->getMessage();
}
?>