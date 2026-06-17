<?php
require_once 'includes/db.php';

try {
    echo "Ensuring salary categories exist in 'expense_categories'...\n";

    $categories = [
        ['slug' => 'sueldo', 'name' => 'Sueldo Básico Conductor'],
        ['slug' => 'auxilio_transporte', 'name' => 'Auxilio de Transporte']
    ];

    foreach ($categories as $cat) {
        $stmt = $pdo->prepare("SELECT id FROM expense_categories WHERE slug = ?");
        $stmt->execute([$cat['slug']]);
        if (!$stmt->fetch()) {
            $stmtInsert = $pdo->prepare("INSERT INTO expense_categories (slug, name) VALUES (?, ?)");
            $stmtInsert->execute([$cat['slug'], $cat['name']]);
            echo "Category '{$cat['name']}' created.\n";
        } else {
            echo "Category '{$cat['name']}' already exists.\n";
        }
    }

    echo "Migration completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>