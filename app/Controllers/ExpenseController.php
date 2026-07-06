<?php
// app/Controllers/ExpenseController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Core\Controller;
use App\Helpers\Audit;
use PDO;
use PDOException;

class ExpenseController extends Controller
{
    public function store()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('expenses.php');
        }

        // CSRF Validation
        validateCsrfToken();

        // Resolve category_id and slug from expense_categories table
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $category_name = null;
        $category_slug = null;
        if ($category_id) {
            $stmtCat = $this->pdo->prepare("SELECT name, slug FROM expense_categories WHERE id = ? AND active = 1");
            $stmtCat->execute([$category_id]);
            $catInfo = $stmtCat->fetch();
            if ($catInfo) {
                $category_name = $catInfo['name'];
                $category_slug = $catInfo['slug'];
            }
        }

        // If the expense is fuel (by slug), calculate amount from gallons and price before validation
        if ($category_slug === 'combustible') {
            $gallons = floatval($_POST['gallons'] ?? 0);
            $price = floatval($_POST['price_per_gallon'] ?? 0);
            $_POST['amount'] = $gallons * $price;
        }

        $required = [
            'amount' => 'numeric',
            'date' => 'date',
            'paid_by' => 'string'
        ];
        $financials = ['amount'];

        $errors = array_merge(validatePOST($required), validateFinancials($financials));

        if (!$category_id) {
            $errors[] = "El campo 'Categoría' es obligatorio.";
        }

        if (empty($_POST['vehicle_id'])) {
            $errors[] = "El campo 'Vehículo' es obligatorio.";
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
            $this->redirect('expense_form.php', isset($_POST['id']) ? ['id' => $_POST['id']] : []);
        }

        $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        $amount = $_POST['amount'];
        $desc = $_POST['description'] ?? '';
        $paid_by = $_POST['paid_by'];

        if (!in_array($paid_by, ['Conductor', 'Propietario'])) {
            $_SESSION['error'] = "El campo 'paid_by' debe ser 'Conductor' o 'Propietario'.";
            $this->redirect('expense_form.php', isset($_POST['id']) ? ['id' => $_POST['id']] : []);
        }

        $trip_id = !empty($_POST['trip_id']) ? $_POST['trip_id'] : null;
        $vehicle_id = !empty($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;
        $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;

        if ($trip_id && !$vehicle_id) {
            $stmtTp = $this->pdo->prepare("SELECT vehicle_id FROM trips WHERE id = ?");
            $stmtTp->execute([$trip_id]);
            $vehicle_id = $stmtTp->fetchColumn();
        }

        // AUTO-LINK LOGIC: If no trip_id, try to find an active trip for this vehicle/date
        if (!$trip_id && $vehicle_id) {
            $stmtActive = $this->pdo->prepare("
                SELECT id FROM trips 
                WHERE vehicle_id = ? 
                AND status NOT IN ('Finalizado', 'Cancelado')
                AND ? >= date_load
                ORDER BY date_load DESC LIMIT 1
            ");
            $stmtActive->execute([$vehicle_id, $date]);
            $found_trip = $stmtActive->fetchColumn();
            if ($found_trip) {
                $trip_id = $found_trip;
                if ($paid_by === 'Propietario') {
                    $desc .= " [Gasto de Propietario en Espera]";
                }
                $desc .= " (Vinculado automáticamente a Viaje #$trip_id)";
                $autoLinked = true;
            }
        }

        // Photo Upload Logic
        $receipt_photo = $_POST['existing_photo'] ?? null;
        if (isset($_FILES['receipt_photo']) && $_FILES['receipt_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/receipts/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);
            $extension = pathinfo($_FILES['receipt_photo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('rec_') . '.' . $extension;
            $targetFile = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['receipt_photo']['tmp_name'], $targetFile)) {
                $receipt_photo = $targetFile;
            }
        }

        try {
            // Begin transaction for data integrity
            $this->pdo->beginTransaction();

            $id = $_POST['id'] ?? null;
            if ($id) {
                $sql = "UPDATE expenses SET 
                    date=?, category_id=?, category_name=?, paid_by=?, amount=?, description=?, trip_id=?, vehicle_id=?, supplier_id=?,
                    payment_method=?, eds_name=?, eds_location=?, eds_state_id=?, eds_city_id=?, invoice_number=?, gallons=?, price_per_gallon=?, invoice_status=?, receipt_photo=?, department=?, city=?
                    WHERE id=?";
                $this->pdo->prepare($sql)->execute([
                    $date,
                    $category_id,
                    $category_name,
                    $paid_by,
                    $amount,
                    $desc,
                    $trip_id,
                    $vehicle_id,
                    $supplier_id,
                    $_POST['payment_method'] ?? null,
                    $_POST['eds_name'] ?? null,
                    $_POST['eds_location'] ?? null,
                    $_POST['eds_state_id'] ?? null,
                    $_POST['eds_city_id'] ?? null,
                    $_POST['invoice_number'] ?? null,
                    !empty($_POST['gallons']) ? $_POST['gallons'] : null,
                    !empty($_POST['price_per_gallon']) ? $_POST['price_per_gallon'] : null,
                    $_POST['invoice_status'] ?? 'Pendiente',
                    $receipt_photo,
                    $_POST['department'] ?? null,
                    $_POST['city'] ?? null,
                    $id
                ]);
                Audit::log('UPDATE', 'EXPENSE', $id, "Actualización de gasto: $category_name");
            } else {
                $sql = "INSERT INTO expenses (
                    date, category_id, category_name, paid_by, amount, description, trip_id, vehicle_id, supplier_id,
                    payment_method, eds_name, eds_location, eds_state_id, eds_city_id, invoice_number, gallons, price_per_gallon, invoice_status, created_by, receipt_photo, department, city
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute([
                    $date,
                    $category_id,
                    $category_name,
                    $paid_by,
                    $amount,
                    $desc,
                    $trip_id,
                    $vehicle_id,
                    $supplier_id,
                    $_POST['payment_method'] ?? null,
                    $_POST['eds_name'] ?? null,
                    $_POST['eds_location'] ?? null,
                    $_POST['eds_state_id'] ?? null,
                    $_POST['eds_city_id'] ?? null,
                    $_POST['invoice_number'] ?? null,
                    !empty($_POST['gallons']) ? $_POST['gallons'] : null,
                    !empty($_POST['price_per_gallon']) ? $_POST['price_per_gallon'] : null,
                    $_POST['invoice_status'] ?? 'Pendiente',
                    $this->userId,
                    $receipt_photo,
                    $_POST['department'] ?? null,
                    $_POST['city'] ?? null
                ]);
                $id = $this->pdo->lastInsertId();
                $auditMsg = "Registro de nuevo gasto: $category_name";
                if (isset($autoLinked) && $autoLinked) {
                    $auditMsg .= " (VINCULADO AUTOMÁTICAMENTE A VIAJE #$trip_id)";
                }
                Audit::log('CREATE', 'EXPENSE', $id, $auditMsg);
            }

            if ($trip_id)
                calculateTripFinancials($trip_id);

            // Trigger alert for Unusual Expenses
            $stmtThreshold = $this->pdo->query("SELECT unusual_expense_threshold FROM config LIMIT 1");
            $threshold = $stmtThreshold->fetchColumn() ?: 1000000;

            if ($amount >= $threshold) {
                $this->createSystemAlert(
                    'unusual_expense',
                    $id,
                    'EXPENSE',
                    "Gasto Inusual Detectado",
                    "Se registró un gasto de " . formatCurrency($amount) . " en la categoría '$category'. Supera el umbral de seguridad.",
                    'high'
                );
            }

            // Commit transaction
            $this->pdo->commit();

            $_SESSION['success'] = "Gasto guardado exitosamente.";
            if ($trip_id) {
                $this->redirect('trip_details.php', ['id' => $trip_id, 'saved' => 1, 'clear_draft' => 'expenseForm']);
            } else {
                $this->redirect('expenses.php', ['saved' => 1, 'clear_draft' => 'expenseForm']);
            }

        } catch (PDOException $e) {
            // Rollback on error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log("Error saving expense: " . $e->getMessage());

            if (isProduction()) {
                $_SESSION['error'] = "Error al guardar el gasto. Por favor intente nuevamente.";
            } else {
                $_SESSION['error'] = "Error al guardar el gasto: " . $e->getMessage();
            }

            $this->redirect('expense_form.php', isset($_POST['id']) ? ['id' => $_POST['id']] : []);
        }
    }

    public function destroy()
    {
        $this->requireAuth();
        $id = $_GET['id'] ?? null;

        if (!$id)
            $this->redirect('expenses.php');

        try {
            $stmt = $this->pdo->prepare("SELECT trip_id FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            $expense = $stmt->fetch();

            $this->pdo->prepare("DELETE FROM expenses WHERE id = ?")->execute([$id]);

            if ($expense && $expense['trip_id']) {
                calculateTripFinancials($expense['trip_id']);
            }

            Audit::log('DELETE', 'EXPENSE', $id, "Eliminación de gasto");
            $_SESSION['success'] = "Gasto eliminado exitosamente.";
            $this->redirect('expenses.php', ['success' => 'deleted']);

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al eliminar el gasto: " . $e->getMessage();
            $this->redirect('expenses.php', ['error' => 'fk_constraint']);
        }
    }

    public function storeMultipleFromPortal($trip_id, $expenses)
    {
        $this->requireAuth();

        $stmtTrip = $this->pdo->prepare("SELECT vehicle_id FROM trips WHERE id = ?");
        $stmtTrip->execute([$trip_id]);
        $trip = $stmtTrip->fetch();
        if (!$trip)
            return;

        foreach ($expenses as $slug => $data) {
            if (empty($data['amount']) || $data['amount'] <= 0)
                continue;

            $category = $slug;
            $amount = (float) str_replace(['$', ','], '', $data['amount']);
            $desc = $data['description'] ?? '';
            $paid_by = 'Conductor';
            $date = date('Y-m-d');

            $gallons = !empty($data['gallons']) ? $data['gallons'] : null;
            $ppg = !empty($data['price_per_gallon']) ? $data['price_per_gallon'] : null;
            $tank_km = !empty($data['tank_mileage']) ? $data['tank_mileage'] : null;
            $method = $data['payment_method'] ?? 'Efectivo';

            try {
                $sql = "INSERT INTO expenses (
                    date, category, paid_by, amount, description, trip_id, vehicle_id,
                    gallons, price_per_gallon, tank_mileage, payment_method, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $this->pdo->prepare($sql)->execute([
                    $date,
                    $category,
                    $paid_by,
                    $amount,
                    $desc,
                    $trip_id,
                    $trip['vehicle_id'],
                    $gallons,
                    $ppg,
                    $tank_km,
                    $method,
                    $this->userId
                ]);

                $id = $this->pdo->lastInsertId();

                $logMsg = ($category === 'combustible' || $category === 'Combustible')
                    ? "Sección Combustible: Tanqueo reportado ($amount)"
                    : "Sección Gastos: $category reportado ($amount)";

                Audit::log('CREATE', 'EXPENSE', $id, "$logMsg (#$trip_id)");

            } catch (PDOException $e) {
                Audit::log('ERROR', 'SYSTEM', 0, "Error auto-gasto portal ($category): " . $e->getMessage());
            }
        }
        calculateTripFinancials($trip_id);
    }
}
