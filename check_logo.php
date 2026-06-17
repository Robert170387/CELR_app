<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT logo_path FROM config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($config);
?>