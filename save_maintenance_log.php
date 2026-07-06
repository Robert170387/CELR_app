<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/security_utils.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    $schedule_id = $_POST['schedule_id'];
    $performed_date = $_POST['performed_date'];
    $performed_at_kms = $_POST['performed_at_kms'];
    $cost = $_POST['cost'];
    $technician = $_POST['technician'];
    $notes = $_POST['notes'];

    $receipt_photo_path = null;
    try {
        $receipt_photo_path = safeUploadFile(
            $_FILES['receipt_photo'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            'uploads/maintenance/',
            [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'webp' => ['image/webp'],
            ],
            'maint',
            5242880
        );
    } catch (RuntimeException $e) {
        header("Location: maintenance_list.php?error=" . urlencode($e->getMessage()));
        exit;
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
