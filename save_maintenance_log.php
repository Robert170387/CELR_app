<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'];
    $performed_date = $_POST['performed_date'];
    $performed_at_kms = $_POST['performed_at_kms'];
    $cost = $_POST['cost'];
    $technician = $_POST['technician'];
    $notes = $_POST['notes'];

    $receipt_photo_path = null;
    if (isset($_FILES['receipt_photo']) && $_FILES['receipt_photo']['error'] === 0) {
        $upload_dir = 'uploads/maintenance/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        $file_ext = pathinfo($_FILES['receipt_photo']['name'], PATHINFO_EXTENSION);
        $filename = 'maint_' . time() . '_' . uniqid() . '.' . $file_ext;
        move_uploaded_file($_FILES['receipt_photo']['tmp_name'], $upload_dir . $filename);
        $receipt_photo_path = $upload_dir . $filename;
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert Log
        $stmtLog = $pdo->prepare("INSERT INTO maintenance_logs (schedule_id, performed_date, performed_at_kms, cost, technician, notes, receipt_photo) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtLog->execute([$schedule_id, $performed_date, $performed_at_kms, $cost, $technician, $notes, $receipt_photo_path]);

        // 2. Update Schedule (recalculate next KMS)
        $stmtSchedule = $pdo->prepare("SELECT interval_kms FROM maintenance_schedules WHERE id = ?");
        $stmtSchedule->execute([$schedule_id]);
        $interval = $stmtSchedule->fetchColumn();

        $next_kms = $performed_at_kms + $interval;

        $updateStmt = $pdo->prepare("UPDATE maintenance_schedules SET 
                                    last_service_kms = ?, 
                                    next_service_kms = ? 
                                    WHERE id = ?");
        $updateStmt->execute([$performed_at_kms, $next_kms, $schedule_id]);

        $pdo->commit();
        header("Location: maintenance_list.php?success=" . urlencode("Servicio registrado. La próxima alerta se activará en " . number_format($next_kms) . " Km."));
    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: maintenance_list.php?error=" . urlencode("Error al registrar: " . $e->getMessage()));
    }
}
