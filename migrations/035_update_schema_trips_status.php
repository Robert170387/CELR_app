<?php
require_once 'includes/db.php';

try {
    echo "Updating trips table schema...\n";

    // Add 'status' column
    try {
        $pdo->exec("ALTER TABLE trips ADD COLUMN status ENUM('En Progreso', 'Finalizado', 'Cancelado') DEFAULT 'En Progreso'");
        echo "Added 'status' column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "'status' column already exists.\n";
        } else {
            throw $e;
        }
    }

    // Backfill Status
    // If date_unload is set and is not in future, set to Finalizado
    $stmt = $pdo->query("UPDATE trips SET status = 'Finalizado' WHERE date_unload IS NOT NULL AND date_unload <= CURDATE()");
    echo "Updated " . $stmt->rowCount() . " trips to 'Finalizado'.\n";

    echo "Schema update completed successfully.\n";

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>