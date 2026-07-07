<?php
require_once 'includes/db.php';
echo '<b>PERSONNEL:</b><br>';
foreach($pdo->query('DESCRIBE personnel')->fetchAll() as $r) echo $r['Field'].'<br>';