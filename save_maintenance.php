<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    validateCsrfToken();

    $id = $_POST['id'] ?? null;
    $vehicle_id = $_POST['vehicle_id'];
    $task_name = $_POST['task_name'];
    $interval_kms = $_POST['interval_kms'];
    $last_service_kms = $_POST['last_service_kms'];
    $next_service_kms = $_POST['next_service_kms'];
    $warning_margin_kms = $_POST['warning_margin_kms'];
    $priority = $_POST['priority'];

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE maintenance_schedules SET 
                vehicle_id = ?, task_name = ?, interval_kms = ?, last_service_kms = ?, 
                next_service_kms = ?, warning_margin_kms = ?, priority = ? 
                WHERE id = ?");
            $stmt->execute([$vehicle_id, $task_name, $interval_kms, $last_service_kms, $next_service_kms, $warning_margin_kms, $priority, $id]);
            $msg = "Tarea de mantenimiento actualizada.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO maintenance_schedules (vehicle_id, task_name, interval_kms, last_service_kms, next_service_kms, warning_margin_kms, priority) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vehicle_id, $task_name, $interval_kms, $last_service_kms, $next_service_kms, $warning_margin_kms, $priority]);
            $msg = "Tarea de mantenimiento programada exitosamente.";
        }

        header("Location: maintenance_list.php?success=" . urlencode($msg));
    } catch (PDOException $e) {
        header("Location: maintenance_list.php?error=" . urlencode("Error al guardar: " . $e->getMessage()));
    }
}
