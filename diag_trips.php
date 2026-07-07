<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/db.php';
echo "Conexion OK<br>";
$r = $pdo->query("DESCRIBE trips");
foreach($r->fetchAll() as $row) echo $row['Field'] . "<br>";
