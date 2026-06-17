<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isset($_GET['id']) || !isset($_GET['trip_id'])) {
    die("Error: ID faltante.");
}

$id = $_GET['id'];
$trip_id = $_GET['trip_id'];

try {
    $pdo->beginTransaction();

    // Delete Payment
    $stmt = $pdo->prepare("DELETE FROM trip_payments WHERE id = ? AND trip_id = ?");
    $stmt->execute([$id, $trip_id]);

    // Recalculate Total Paid
    $stmtSum = $pdo->prepare("SELECT SUM(amount) FROM trip_payments WHERE trip_id = ?");
    $stmtSum->execute([$trip_id]);
    $total_paid = $stmtSum->fetchColumn() ?: 0;

    // Update Trip Cache
    $stmtUpdate = $pdo->prepare("UPDATE trips SET final_pay_received = ? WHERE id = ?");
    $stmtUpdate->execute([$total_paid, $trip_id]);

    $pdo->commit();

    header("Location: trip_details.php?id=$trip_id&payment_deleted=1");

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error eliminando el pago: " . $e->getMessage());
}
?>