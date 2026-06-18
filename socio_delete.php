<?php
/**
 * Soft delete / reactivar socio
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

$id         = (int)($_GET['id'] ?? 0);
$reactivar  = isset($_GET['reactivar']) && $_GET['reactivar'] == '1';

if (!$id) {
    header('Location: socios.php');
    exit;
}

$nuevoEstado = $reactivar ? 1 : 0;

$stmt = $pdo->prepare("UPDATE socios SET active = ? WHERE id = ?");
$stmt->execute([$nuevoEstado, $id]);

header('Location: socios.php?msg=deleted');
exit;
