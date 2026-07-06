<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'app/Helpers/Audit.php';

requireRole('admin');

$id = $_GET['id'] ?? null;
$dir = $_GET['dir'] ?? '';
$parent_id = $_GET['parent_id'] ?? null;

if (!$id || !in_array($dir, ['up', 'down'])) {
    header('Location: categories.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, parent_id, sort_order FROM expense_categories WHERE id = ?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch();

    if (!$cat) {
        header('Location: categories.php');
        exit;
    }

    $scopeField = $cat['parent_id'] ? 'parent_id' : 'parent_id IS NULL';
    $scopeValue = $cat['parent_id'] ? $cat['parent_id'] : null;

    // Find the adjacent category to swap order with
    $compare = ($dir === 'up') ? '<' : '>';
    $orderBy = ($dir === 'up') ? 'DESC' : 'ASC';

    if ($scopeValue) {
        $stmtAdj = $pdo->prepare("SELECT id, sort_order FROM expense_categories WHERE parent_id = ? AND sort_order $compare ? ORDER BY sort_order $orderBy LIMIT 1");
        $stmtAdj->execute([$scopeValue, $cat['sort_order']]);
    } else {
        $stmtAdj = $pdo->prepare("SELECT id, sort_order FROM expense_categories WHERE parent_id IS NULL AND sort_order $compare ? ORDER BY sort_order $orderBy LIMIT 1");
        $stmtAdj->execute([$cat['sort_order']]);
    }

    $adj = $stmtAdj->fetch();

    if ($adj) {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE expense_categories SET sort_order = ? WHERE id = ?")->execute([$adj['sort_order'], $id]);
        $pdo->prepare("UPDATE expense_categories SET sort_order = ? WHERE id = ?")->execute([$cat['sort_order'], $adj['id']]);
        $pdo->commit();
        \App\Helpers\Audit::log('UPDATE', 'CATEGORY', $id, "Reordenamiento de categoría");
    }

    header('Location: categories.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error'] = "Error al reordenar: " . $e->getMessage();
    header('Location: categories.php');
    exit;
}
