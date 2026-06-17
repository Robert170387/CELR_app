<?php
// app/Controllers/UserController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Core\Controller;
use App\Helpers\Audit;
use PDO;
use PDOException;

class UserController extends Controller
{
    public function store()
    {
        $this->requireRole('Admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('users.php');
        }

        $id = $_POST['id'] ?? null;
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        $status = $_POST['status'] ?? 'active';
        $related_client_id = !empty($_POST['related_client_id']) ? $_POST['related_client_id'] : null;
        $related_personnel_id = !empty($_POST['related_personnel_id']) ? $_POST['related_personnel_id'] : null;

        if ($role !== 'cliente')
            $related_client_id = null;
        if ($role !== 'conductor')
            $related_personnel_id = null;

        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/avatars/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);
            $fileExt = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $avatarPath = $uploadDir . uniqid('user_', true) . '.' . $fileExt;
                move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarPath);
            }
        }

        try {
            if ($id) {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetchColumn() > 0)
                    die("El nombre de usuario ya está en uso.");

                $sql = "UPDATE users SET username = ?, full_name = ?, role = ?, status = ?, related_client_id = ?, related_personnel_id = ?";
                $params = [$username, $full_name, $role, $status, $related_client_id, $related_personnel_id];

                if (!empty($password)) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($password, PASSWORD_BCRYPT);
                }
                if ($avatarPath) {
                    $sql .= ", avatar = ?";
                    $params[] = $avatarPath;
                }
                $sql .= " WHERE id = ?";
                $params[] = $id;

                $this->pdo->prepare($sql)->execute($params);
                Audit::log('UPDATE', 'USER', $id, "Actualización de usuario: $username");
                $this->redirect('users.php', ['updated' => 1]);
            } else {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0)
                    die("El nombre de usuario ya existe.");

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $this->pdo->prepare("INSERT INTO users (username, full_name, password, role, status, avatar, related_client_id, related_personnel_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $full_name, $hash, $role, $status, $avatarPath, $related_client_id, $related_personnel_id]);
                $id = $this->pdo->lastInsertId();
                Audit::log('CREATE', 'USER', $id, "Registro de nuevo usuario: $username");
                $this->redirect('users.php', ['created' => 1]);
            }

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al guardar usuario: " . $e->getMessage();
            $this->redirect('user_form.php', $id ? ['id' => $id] : []);
        }
    }
}
