<?php
// app/Controllers/CategoryController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Core\Controller;
use App\Helpers\Audit;
use PDO;
use PDOException;

class CategoryController extends Controller
{
    public function store()
    {
        $this->requireRole('Admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('categories.php');
        }

        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        $id = $_POST['id'] ?? null;

        if (empty($name)) {
            $_SESSION['error'] = "El nombre es obligatorio.";
            $this->redirect('categories.php');
        }

        try {
            if ($id) {
                $stmt = $this->pdo->prepare("UPDATE expense_categories SET name = ?, type = ? WHERE id = ?");
                $stmt->execute([$name, $type, $id]);
                Audit::log('UPDATE', 'CATEGORY', $id, "Actualización de categoría: $name");
            } else {
                $slug = $this->createSlug($name);
                // Check uniqueness
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM expense_categories WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetchColumn() > 0) {
                    $slug .= '_' . time();
                }

                $stmt = $this->pdo->prepare("INSERT INTO expense_categories (name, slug, type) VALUES (?, ?, ?)");
                $stmt->execute([$name, $slug, $type]);
                $id = $this->pdo->lastInsertId();
                Audit::log('CREATE', 'CATEGORY', $id, "Registro de nueva categoría: $name");
            }

            $this->redirect('categories.php', ['success' => 1]);

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al guardar la categoría: " . $e->getMessage();
            $this->redirect('categories.php');
        }
    }

    public function destroy()
    {
        $this->requireRole('Admin');
        $id = $_GET['id'] ?? null;

        if (!$id) {
            $this->redirect('categories.php');
        }

        try {
            $stmt = $this->pdo->prepare("SELECT slug FROM expense_categories WHERE id = ?");
            $stmt->execute([$id]);
            $cat = $stmt->fetch();

            if ($cat) {
                $stmtUsage = $this->pdo->prepare("SELECT COUNT(*) FROM expenses WHERE category = ?");
                $stmtUsage->execute([$cat['slug']]);
                if ($stmtUsage->fetchColumn() > 0) {
                    $this->redirect('categories.php', ['error' => 'fk_constraint']);
                }

                $stmtDel = $this->pdo->prepare("DELETE FROM expense_categories WHERE id = ?");
                $stmtDel->execute([$id]);
                Audit::log('DELETE', 'CATEGORY', $id, "Eliminación de categoría");
            }

            $this->redirect('categories.php', ['success' => 'deleted']);

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al eliminar la categoría: " . $e->getMessage();
            $this->redirect('categories.php');
        }
    }

    private function createSlug($str)
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/[^\p{L}\p{N}]+/u', '_', $str);
        return trim($str, '_');
    }
}
