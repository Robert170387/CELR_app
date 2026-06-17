<?php
require 'includes/db.php';
$cols = $pdo->query("DESCRIBE locations")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('locations_schema.txt', print_r($cols, true));

$cols = $pdo->query("DESCRIBE trips")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('trips_schema.txt', print_r($cols, true));
