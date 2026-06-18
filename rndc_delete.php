<?php
/**
 * Anular manifiesto RNDC (soft delete — nunca elimina)
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $pdo->prepare("UPDATE manifiestos_rndc SET estado='Anulado' WHERE id=?")->execute([$id]);
}
header('Location: rndc.php?msg=anulado');
exit;
