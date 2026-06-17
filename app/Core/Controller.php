<?php
// app/Core/Controller.php

namespace App\Core;

use PDO;
use PDOException;

class Controller
{
    protected $pdo;
    protected $userId;

    public function __construct()
    {
        global $pdo; // Use global connection for compatibility
        $this->pdo = $pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    protected function requireAuth()
    {
        if (!$this->userId) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['status' => 'error', 'message' => 'No autorizado'], 403);
            }
            $this->redirect('login.php');
        }
    }

    protected function requireRole($roles)
    {
        $this->requireAuth();
        if (!is_array($roles))
            $roles = [$roles];

        $userRole = strtolower($_SESSION['role'] ?? '');
        $roles = array_map('strtolower', $roles);

        // Admin has access to everything
        if ($userRole === 'admin')
            return true;

        if (!in_array($userRole, $roles)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Permisos insuficientes'], 403);
            }
            $_SESSION['error'] = "No tienes permiso para acceder a esta sección.";
            $this->redirect('index.php');
        }
    }

    protected function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    protected function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect($url, $params = [])
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        header("Location: $url");
        exit;
    }

    protected function validatePost($rules)
    {
        $errors = [];
        foreach ($rules as $field => $type) {
            if (!isset($_POST[$field]) || (is_string($_POST[$field]) && trim($_POST[$field]) === '')) {
                $errors[] = "Campo requerido: $field";
                continue;
            }
            // Basic type checking - extend as needed
            if ($type == 'numeric' && !is_numeric($_POST[$field])) {
                $errors[] = "$field debe ser numérico";
            }
        }
        return $errors;
    }

    protected function createSystemAlert($type, $entityId, $entityType, $title, $message, $priority = 'normal')
    {
        try {
            $sql = "INSERT INTO system_alerts (type, entity_id, entity_type, title, message, priority) VALUES (?, ?, ?, ?, ?, ?)";
            $this->pdo->prepare($sql)->execute([$type, $entityId, $entityType, $title, $message, $priority]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>