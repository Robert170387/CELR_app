<?php
// api_supplier_location.php - Returns geographic info of a supplier
require_once 'includes/db.php';
header('Content-Type: application/json');

$supplier_id = intval($_GET['supplier_id'] ?? 0);
if (!$supplier_id) {
    echo json_encode(['error' => true, 'message' => 'Missing supplier_id']);
    exit;
}

$stmt = $pdo->prepare("SELECT department, city FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo json_encode(['error' => false, 'department' => $row['department'] ?? '', 'city' => $row['city'] ?? '']);
} else {
    echo json_encode(['error' => true, 'message' => 'Supplier not found']);
}
