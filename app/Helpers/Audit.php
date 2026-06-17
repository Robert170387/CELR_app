<?php
// app/Helpers/Audit.php
namespace App\Helpers;

class Audit
{

    public static function log($action, $entityType, $entityId, $details = null)
    {
        global $pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ip]);
        } catch (\PDOException $e) {
            // Fail silently or log to file
            error_log("Audit Error: " . $e->getMessage());
        }
    }
}
?>