<?php
// app/Controllers/PersonnelController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Core\Controller;
use App\Helpers\Audit;
use PDO;
use PDOException;

class PersonnelController extends Controller
{
    public function store()
    {
        $this->requireRole(['Admin', 'Staff']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('personnel.php');
        }

        $id = $_POST['id'] ?? null;
        $type = $_POST['type'] ?? 'Conductor';
        $firstname = $_POST['firstname'] ?? '';
        $lastname = $_POST['lastname'] ?? '';
        $doc_num = $_POST['document_number'] ?? '';

        try {
            $data = [
                $type,
                $_POST['document_type'] ?? 'CC',
                $doc_num,
                $firstname,
                $lastname,
                $_POST['gender'] ?? 'M',
                $_POST['address'] ?? '',
                $_POST['phone'] ?? '',
                $_POST['city'] ?? '',
                $_POST['department'] ?? '',
                $_POST['country'] ?? 'Colombia',
                $_POST['license_number'] ?? '',
                $_POST['license_category'] ?? '',
                !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null,
                !empty($_POST['date_entry']) ? $_POST['date_entry'] : null,
                !empty($_POST['date_exit']) ? $_POST['date_exit'] : null,
                !empty($_POST['salary_basic']) ? $_POST['salary_basic'] : 0,
                !empty($_POST['transport_assistance']) ? $_POST['transport_assistance'] : 0,
                !empty($_POST['salary_variable']) ? $_POST['salary_variable'] : 0,
                !empty($_POST['salary_internal']) ? $_POST['salary_internal'] : 0,
                $_POST['bank_account'] ?? ''
            ];

            if ($id) {
                $sql = "UPDATE personnel SET 
                    type = ?, document_type = ?, document_number = ?, firstname = ?, lastname = ?, gender = ?,
                    address = ?, phone = ?, city = ?, department = ?, country = ?,
                    license_number = ?, license_category = ?, license_expiry = ?,
                    date_entry = ?, date_exit = ?,
                    salary_basic = ?, transport_assistance = ?, salary_variable = ?, salary_internal = ?, bank_account = ?
                    WHERE id = ?";
                $data[] = $id;
                $this->pdo->prepare($sql)->execute($data);
                Audit::log('UPDATE', 'PERSONNEL', $id, "Actualización de personal: $firstname $lastname");
            } else {
                $sql = "INSERT INTO personnel (
                    type, document_type, document_number, firstname, lastname, gender,
                    address, phone, city, department, country,
                    license_number, license_category, license_expiry,
                    date_entry, date_exit,
                    salary_basic, transport_assistance, salary_variable, salary_internal, bank_account
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute($data);
                $id = $this->pdo->lastInsertId();
                Audit::log('CREATE', 'PERSONNEL', $id, "Registro de nuevo personal: $firstname $lastname");
            }

            $this->redirect('personnel.php', ['success' => 1]);

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al guardar personal: " . $e->getMessage();
            $this->redirect('personnel_form.php', $id ? ['id' => $id] : []);
        }
    }

    public function destroy()
    {
        $this->requireRole('Admin');
        $id = $_GET['id'] ?? null;

        if (!$id)
            $this->redirect('personnel.php');

        try {
            $this->pdo->prepare("DELETE FROM personnel WHERE id = ?")->execute([$id]);
            Audit::log('DELETE', 'PERSONNEL', $id, "Eliminación de personal");
            $this->redirect('personnel.php', ['success' => 'deleted']);
        } catch (PDOException $e) {
            $this->redirect('personnel.php', ['error' => 'fk_constraint']);
        }
    }
}
