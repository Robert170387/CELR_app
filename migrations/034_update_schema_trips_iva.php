<?php
require_once 'includes/db.php';

try {
    echo "Adding IVA and ReteIVA to trips table...<br>";

    $pdo->exec("ALTER TABLE trips ADD COLUMN percent_iva DECIMAL(5,2) DEFAULT 0.00 AFTER percent_rete_ica");
    $pdo->exec("ALTER TABLE trips ADD COLUMN value_iva DECIMAL(15,2) DEFAULT 0.00 AFTER value_rete_ica");
    $pdo->exec("ALTER TABLE trips ADD COLUMN percent_rete_iva DECIMAL(5,2) DEFAULT 0.00 AFTER percent_iva");
    $pdo->exec("ALTER TABLE trips ADD COLUMN value_rete_iva DECIMAL(15,2) DEFAULT 0.00 AFTER value_iva");

    echo "<b style='color:green;'>Success: Trips table updated with IVA fields.</b>";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<b style='color:orange;'>Info: Columns already exist.</b>";
    } else {
        echo "<b style='color:red;'>Error updating schema:</b> " . $e->getMessage();
    }
}
?>