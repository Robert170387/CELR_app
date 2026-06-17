<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate
    if (empty($_POST['trip_id']) || empty($_POST['amount']) || empty($_POST['payment_date'])) {
        die("Error: Faltan datos obligatorios (Viaje, Monto, Fecha).");
    }

    $trip_id = $_POST['trip_id'];
    $amount = str_replace(',', '', $_POST['amount']); // Remove commas if any
    $date = $_POST['payment_date'];
    $method = $_POST['payment_method'];
    $concept = $_POST['payment_concept'] ?? 'Abono';
    $reference = $_POST['reference'] ?? '';
    $notes = $_POST['notes'] ?? '';

    try {
        $pdo->beginTransaction();

        // Insert Payment
        $stmt = $pdo->prepare("INSERT INTO trip_payments (trip_id, amount, payment_concept, payment_date, payment_method, reference, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$trip_id, $amount, $concept, $date, $method, $reference, $notes]);

        // Recalculate Total Paid
        $stmtSum = $pdo->prepare("SELECT SUM(amount) FROM trip_payments WHERE trip_id = ?");
        $stmtSum->execute([$trip_id]);
        $total_paid = $stmtSum->fetchColumn() ?: 0;

        // Update Trip Cache
        $stmtUpdate = $pdo->prepare("UPDATE trips SET final_pay_received = ? WHERE id = ?");
        $stmtUpdate->execute([$total_paid, $trip_id]);

        $pdo->commit();

        header("Location: trip_details.php?id=$trip_id&payment_saved=1");

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error guardando el pago: " . $e->getMessage());
    }
}
?>