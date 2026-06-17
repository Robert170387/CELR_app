<?php
// app/Controllers/SupplierController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Core\Controller;
use App\Helpers\Audit;
use PDO;
use PDOException;

class SupplierController extends Controller
{
    public function store()
    {
        $this->requireRole(['Admin', 'Staff']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('suppliers.php');
        }

        $id = $_POST['id'] ?? null;
        $person_type = $_POST['person_type'] ?? 'Física';
        $tax_regime = $_POST['tax_regime'] ?? 'Simplificado';
        $nit = $_POST['nit'] ?? '';
        $address = $_POST['address'] ?? '';
        $department = $_POST['department'] ?? '';
        $city = $_POST['city'] ?? '';
        $bank_name = $_POST['bank_name'] ?? '';
        $account_type = $_POST['account_type'] ?? '';
        $bank_account = $_POST['bank_account'] ?? '';

        if ($person_type === 'Jurídica') {
            $business_name = $_POST['business_name'] ?? '';
            $firstname = $lastname1 = $lastname2 = null;
        } else {
            $business_name = null;
            $firstname = $_POST['firstname'] ?? '';
            $lastname1 = $_POST['lastname1'] ?? '';
            $lastname2 = $_POST['lastname2'] ?? '';
        }

        try {
            if ($id) {
                $sql = "UPDATE suppliers SET 
                    person_type = ?, tax_regime = ?, nit = ?, 
                    firstname = ?, lastname1 = ?, lastname2 = ?, business_name = ?,
                    address = ?, department = ?, city = ?, 
                    bank_name = ?, bank_account = ?, account_type = ?
                    WHERE id = ?";
                $this->pdo->prepare($sql)->execute([
                    $person_type,
                    $tax_regime,
                    $nit,
                    $firstname,
                    $lastname1,
                    $lastname2,
                    $business_name,
                    $address,
                    $department,
                    $city,
                    $bank_name,
                    $bank_account,
                    $account_type,
                    $id
                ]);
                Audit::log('UPDATE', 'SUPPLIER', $id, "Actualización de proveedor");
            } else {
                $sql = "INSERT INTO suppliers (
                     person_type, tax_regime, nit, 
                     firstname, lastname1, lastname2, business_name,
                     address, department, city, 
                     bank_name, bank_account, account_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute([
                    $person_type,
                    $tax_regime,
                    $nit,
                    $firstname,
                    $lastname1,
                    $lastname2,
                    $business_name,
                    $address,
                    $department,
                    $city,
                    $bank_name,
                    $bank_account,
                    $account_type
                ]);
                $id = $this->pdo->lastInsertId();
                Audit::log('CREATE', 'SUPPLIER', $id, "Registro de nuevo proveedor");
            }

            $this->redirect('suppliers.php', ['success' => 1]);

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al guardar proveedor: " . $e->getMessage();
            $this->redirect('suppliers.php');
        }
    }

    public function destroy()
    {
        $this->requireRole('Admin');
        $id = $_GET['id'] ?? null;

        if (!$id)
            $this->redirect('suppliers.php');

        try {
            $this->pdo->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);
            Audit::log('DELETE', 'SUPPLIER', $id, "Eliminación de proveedor");
            $this->redirect('suppliers.php', ['success' => 'deleted']);
        } catch (PDOException $e) {
            $this->redirect('suppliers.php', ['error' => 'fk_constraint']);
        }
    }
}
