<?php
require_once 'includes/db.php';
echo "<b>PERSONNEL columns:</b><br>";
foreach($pdo->query("DESCRIBE personnel")->fetchAll() as $r) echo $r['Field']."<br>";
