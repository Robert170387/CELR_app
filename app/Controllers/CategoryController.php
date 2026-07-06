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

        // CSRF Validation
        validateCsrfToken();

        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        $id = $_POST['id'] ?? null;
        $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
        $sort_order = !empty($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;

        // Validar tipo de categoría
        $validTypes = ['viaje', 'vehiculo_fijo', 'administrativo', 'especial'];
        if (!in_array($type, $validTypes)) {
            $_SESSION['error'] = "Tipo de categoría inválido.";
            $this->redirect('categories.php');
        }

        // Si es una subcategoría, hereda el tipo de su padre
        if ($parent_id) {
            $stmt = $this->pdo->prepare("SELECT type FROM expense_categories WHERE id = ?");
            $stmt->execute([$parent_id]);
            $parent_type = $stmt->fetchColumn();
            if ($parent_type) {
                $type = $parent_type;
            }
        }

        if (empty($name)) {
            $_SESSION['error'] = "El nombre es obligatorio.";
            $this->redirect('categories.php');
        }

        try {
            if ($id) {
                // Check if this category has children and parent_id changed
                $oldCat = $this->pdo->prepare("SELECT parent_id, type FROM expense_categories WHERE id = ?");
                $oldCat->execute([$id]);
                $old = $oldCat->fetch();
                if ($old && $old['parent_id'] != $parent_id) {
                    $hasChildren = $this->pdo->prepare("SELECT COUNT(*) FROM expense_categories WHERE parent_id = ?");
                    $hasChildren->execute([$id]);
                    if ($hasChildren->fetchColumn() > 0) {
                        $_SESSION['error'] = "No se puede cambiar el padre de una categoría que tiene subcategorías. Reasigne o elimine las subcategorías primero.";
                        $this->redirect('category_form.php', ['id' => $id]);
                        return;
                    }
                }

                $stmt = $this->pdo->prepare("UPDATE expense_categories SET name = ?, type = ?, parent_id = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$name, $type, $parent_id, $sort_order, $id]);

                // Cascade type change to children if this category is a parent
                if ($old && $old['type'] != $type) {
                    $updateChildren = $this->pdo->prepare("UPDATE expense_categories SET type = ? WHERE parent_id = ?");
                    $updateChildren->execute([$type, $id]);
                }

                Audit::log('UPDATE', 'CATEGORY', $id, "Actualización de categoría: $name");
            } else {
                $slug = $this->createSlug($name);
                // Check uniqueness
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM expense_categories WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetchColumn() > 0) {
                    $slug .= '_' . time();
                }

                $stmt = $this->pdo->prepare("INSERT INTO expense_categories (name, slug, type, parent_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $type, $parent_id]);
                $id = $this->pdo->lastInsertId();
                Audit::log('CREATE', 'CATEGORY', $id, "Registro de nueva categoría: $name");
            }

            $this->redirect('categories.php', ['success' => 1]);

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al guardar la categoría: " . $e->getMessage();
            $this->redirect('categories.php');
        }
    }

    public function getCategoriesByType($type)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM expense_categories WHERE type = ? AND active = 1 ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public function getCategoryTree($type = null)
    {
        $sql = "SELECT * FROM expense_categories WHERE active = 1";
        if ($type) {
            $sql .= " AND type = ?";
        }
        $sql .= " ORDER BY FIELD(type,'viaje','vehiculo_fijo','administrativo','especial'), sort_order ASC, name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        if ($type) {
            $stmt->execute([$type]);
        } else {
            $stmt->execute();
        }
        
        $categories = $stmt->fetchAll();
        
        // Build hierarchical tree
        $tree = [];
        foreach ($categories as $category) {
            $tree[$category['id']] = $category;
        }
        
        // Build parent-child relationships
        $rootCategories = [];
        $childCategories = [];
        
        foreach ($tree as $id => $category) {
            if ($category['parent_id'] === null) {
                $rootCategories[$id] = $category;
            } else {
                if (!isset($childCategories[$category['parent_id']])) {
                    $childCategories[$category['parent_id']] = [];
                }
                $childCategories[$category['parent_id']][$id] = $category;
            }
        }
        
        return [
            'root_categories' => $rootCategories,
            'child_categories' => $childCategories,
            'all_categories' => $categories
        ];
    }

    public function getCategoryStats($categoryId)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as expense_count, SUM(amount) as total_amount FROM expenses WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        return $stmt->fetch();
    }

    public function getCategoryHierarchyWithStats($startDate, $endDate)
    {
        $sql = "
        WITH RECURSIVE category_hierarchy AS (
            SELECT 
                id,
                name,
                slug,
                type,
                parent_id,
                1 as level,
                CAST(id AS CHAR(255)) as path,
                '' as path_name
            FROM expense_categories
            WHERE parent_id IS NULL AND active = 1
            
            UNION ALL
            
            SELECT 
                c.id,
                c.name,
                c.slug,
                c.type,
                c.parent_id,
                ch.level + 1 as level,
                CONCAT(ch.path, ',', c.id) as path,
                CONCAT(ch.path_name, ' > ', c.name) as path_name
            FROM expense_categories c
            JOIN category_hierarchy ch ON c.parent_id = ch.id
            WHERE c.active = 1
        )
        SELECT 
            ch.id,
            ch.name,
            ch.slug,
            ch.type,
            ch.level,
            ch.path_name,
            COALESCE(SUM(e.amount), 0) as total_amount,
            COALESCE(COUNT(e.id), 0) as expense_count
        FROM category_hierarchy ch
        LEFT JOIN expenses e ON ch.id = e.category_id 
                             AND e.date BETWEEN ? AND ?
        WHERE ch.active = 1
        GROUP BY ch.id, ch.name, ch.slug, ch.type, ch.level, ch.path_name
        ORDER BY ch.path, ch.level;
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
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
                $stmtUsage = $this->pdo->prepare("SELECT COUNT(*) FROM expenses WHERE category_id = ?");
                $stmtUsage->execute([$id]);
                if ($stmtUsage->fetchColumn() > 0) {
                    $this->redirect('categories.php', ['error' => 'fk_constraint']);
                }

                $stmtChildren = $this->pdo->prepare("SELECT COUNT(*) FROM expense_categories WHERE parent_id = ?");
                $stmtChildren->execute([$id]);
                if ($stmtChildren->fetchColumn() > 0) {
                    $_SESSION['error'] = "No se puede eliminar la categoría porque contiene subcategorías.";
                    $this->redirect('categories.php');
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
