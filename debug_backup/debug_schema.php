<?php
require_once __DIR__ . '/includes/security_utils.php';
requireInternalToolAccess();

require 'includes/db.php';
$tables = ['locations', 'loc_countries', 'loc_states', 'loc_cities', 'trips'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    try {
        $cols = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        print_r($cols);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
