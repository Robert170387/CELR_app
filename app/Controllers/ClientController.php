<?php
// app/Controllers/ClientController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Core\Controller;
use App\Helpers\Audit;
use PDO;
use PDOException;

class ClientController extends Controller
{
    public function store()
    {
        $this->requireRole(['Admin', 'Staff']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('clients.php');
        }

        // CSRF Validation
        validateCsrfToken();

        $person_type = $_POST['person_type'] ?? '';
        $errors = [];

        if (!in_array($person_type, ['Física', 'Jurídica'])) {
            $errors[] = "Tipo de persona inválido.";
        }

        if ($person_type === 'Física') {
            if (empty(trim($_POST['firstname'] ?? '')))
                $errors[] = "El nombre es obligatorio para persona física.";
            if (empty(trim($_POST['lastname1'] ?? '')))
                $errors[] = "El primer apellido es obligatorio para persona física.";
        } elseif ($person_type === 'Jurídica') {
            if (empty(trim($_POST['business_name'] ?? '')))
                $errors[] = "La razón social es obligatoria para persona jurídica.";
        }

        if (empty(trim($_POST['email'] ?? '')) && empty(trim($_POST['phone'] ?? '')) && empty(trim($_POST['mobile'] ?? ''))) {
            $errors[] = "Debe proporcionar al menos un método de contacto (email, teléfono o móvil).";
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
            $this->redirect('client_form.php', isset($_POST['id']) ? ['id' => $_POST['id']] : []);
        }

        try {
            $id = $_POST['id'] ?? null;
            $firstname = !empty($_POST['firstname']) ? trim($_POST['firstname']) : '';
            $lastname1 = !empty($_POST['lastname1']) ? trim($_POST['lastname1']) : '';
            $lastname2 = !empty($_POST['lastname2']) ? trim($_POST['lastname2']) : '';
            $business_name = !empty($_POST['business_name']) ? trim($_POST['business_name']) : '';
            $legal_id = !empty($_POST['legal_id']) ? trim($_POST['legal_id']) : null;
            $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
            $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
            $mobile = !empty($_POST['mobile']) ? trim($_POST['mobile']) : null;
            $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
            $country = !empty($_POST['country']) ? trim($_POST['country']) : 'Costa Rica';
            $department = !empty($_POST['department']) ? trim($_POST['department']) : null;
            $city = !empty($_POST['city']) ? trim($_POST['city']) : null;
            $postal_code = !empty($_POST['postal_code']) ? trim($_POST['postal_code']) : null;
            $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
            $active = $_POST['active'] ?? 1;

            if ($person_type === 'Jurídica') {
                $name = $business_name;
            } else {
                $name = trim("$firstname $lastname1 $lastname2");
            }

            $data = [
                $name,
                $person_type,
                $firstname !== '' ? $firstname : null,
                $lastname1 !== '' ? $lastname1 : null,
                $lastname2 !== '' ? $lastname2 : null,
                $business_name !== '' ? $business_name : null,
                $legal_id,
                $email,
                $phone,
                $mobile,
                $address,
                $country,
                $department,
                $city,
                $postal_code,
                $notes,
                $active
            ];

            if ($id) {
                $sql = "UPDATE clients SET 
                        name = ?, person_type = ?, firstname = ?, lastname1 = ?, lastname2 = ?, business_name = ?, legal_id = ?,
                        email = ?, phone = ?, mobile = ?, address = ?, country = ?, department = ?, city = ?, postal_code = ?, notes = ?, active = ?
                        WHERE id = ?";
                $data[] = $id;
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($data);
                $_SESSION['success'] = "Cliente actualizado exitosamente.";
                Audit::log('UPDATE', 'CLIENT', $id, "Actualización de cliente");
            } else {
                $sql = "INSERT INTO clients (
                        name, person_type, firstname, lastname1, lastname2, business_name, legal_id,
                        email, phone, mobile, address, country, department, city, postal_code, notes, active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($data);
                $id = $this->pdo->lastInsertId();
                $_SESSION['success'] = "Cliente creado exitosamente.";
                Audit::log('CREATE', 'CLIENT', $id, "Registro de nuevo cliente");
            }

            $this->redirect('client_details.php', ['id' => $id]);

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al guardar el cliente: " . $e->getMessage();
            $this->redirect('client_form.php', isset($_POST['id']) ? ['id' => $_POST['id']] : []);
        }
    }

    public function destroy()
    {
        $this->requireRole(['Admin']);
        $id = $_GET['id'] ?? null;

        if (!$id) {
            $_SESSION['error'] = "ID de cliente no especificado.";
            $this->redirect('clients.php');
        }

        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM trips WHERE client_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "No se puede eliminar el cliente porque tiene viajes asociados.";
                $this->redirect('client_details.php', ['id' => $id]);
            }

            $stmt = $this->pdo->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['success'] = "Cliente eliminado exitosamente.";
            Audit::log('DELETE', 'CLIENT', $id, "Eliminación de cliente");
            $this->redirect('clients.php');

        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al eliminar el cliente: " . $e->getMessage();
            $this->redirect('client_details.php', ['id' => $id]);
        }
    }
}
