<?php
require_once __DIR__ . '/includes/security_utils.php';
requireInternalToolAccess();

require_once 'includes/db.php';
try {
    $all_states = $pdo->query("SELECT s.id, s.name FROM loc_states s JOIN loc_countries c ON s.country_id = c.id WHERE c.name = 'Colombia' ORDER BY s.name ASC")->fetchAll();
    echo "Count: " . count($all_states) . "\n";
    print_r($all_states);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
