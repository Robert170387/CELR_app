<?php
require_once __DIR__ . '/includes/security_utils.php';
requireInternalToolAccess();

require_once 'includes/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($users) {
        echo "Users found:\n";
        foreach ($users as $user) {
            echo "ID: " . $user['id'] . ", User: " . $user['username'] . ", Role: " . $user['role'] . "\n";
        }
    } else {
        echo "No users found in database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
