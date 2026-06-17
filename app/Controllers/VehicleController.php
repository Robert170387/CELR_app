<?php
// app/Controllers/VehicleController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
require_once __DIR__ . '/../../includes/db.php';

use App\Core\Controller;
use App\Helpers\Audit;
use PDO;
use PDOException;

class VehicleController extends Controller
{

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('vehicles.php');
        }

        // --- VALIDATION LAYER ---
        $rules = [
            'placa' => 'string',
            'brand' => 'string',
            'category' => 'string'
        ];

        $errors = $this->validatePost($rules);
        if (!empty($errors)) {
            // Ideally we'd flash errors to session, for now we redirect with generic error
            $this->redirect('vehicle_form.php', ['error' => 'validation_error']);
        }

        // --- COLLECT DATA ---
        $data = $this->collectData();

        try {
            if ($data['id']) {
                $this->update($data);
            } else {
                $this->create($data);
            }
        } catch (PDOException $e) {
            // Log error internally if possible
            // echo $e->getMessage();
            $this->redirect('vehicles.php', ['error' => 'db_error']);
        }
    }

    private function collectData()
    {
        return [
            'id' => $_POST['id'] ?? null,
            'placa' => strtoupper($_POST['placa']),
            'brand' => $_POST['brand'],
            'model' => $_POST['model'] ?? '',
            'category' => $_POST['category'],
            'model_year' => $_POST['model_year'] ?? 0,
            'color' => $_POST['color'] ?? '',
            'serial' => $_POST['serial_number'] ?? '',
            'motor' => $_POST['motor_number'] ?? '',
            'chasis' => $_POST['chasis_number'] ?? '',
            'displacement' => $_POST['displacement'] ?? 0,
            'max_load' => $_POST['max_load'] ?? 0,
            'city' => $_POST['register_city'] ?? '',
            'department' => $_POST['register_department'] ?? null,
            'country' => $_POST['register_country'] ?? 'Colombia',
            'active' => isset($_POST['active']) ? $_POST['active'] : 1,

            // Expiration
            'exp_soat' => !empty($_POST['expiry_soat']) ? $_POST['expiry_soat'] : null,
            'exp_tecno' => !empty($_POST['expiry_tecno']) ? $_POST['expiry_tecno'] : null,
            'exp_policy' => !empty($_POST['expiry_policy']) ? $_POST['expiry_policy'] : null,

            // Ownership
            'default_driver_id' => !empty($_POST['default_driver_id']) ? $_POST['default_driver_id'] : null,
            'ownership_type' => $_POST['ownership_type'] ?? 'Propio',
            'partner_id' => !empty($_POST['partner_id']) ? $_POST['partner_id'] : null,
            'partner_percentage' => !empty($_POST['partner_percentage']) ? $_POST['partner_percentage'] : 0,
            'satrack_id' => $_POST['satrack_id'] ?? null,
        ];
    }

    private function create($data)
    {
        $sql = "INSERT INTO vehicles (
            placa, brand, model, 
            category, model_year, color, 
            serial_number, motor_number, chasis_number, 
            displacement, max_load, 
            register_city, register_department, register_country, 
            expiry_soat, expiry_tecno, expiry_policy,
            default_driver_id, ownership_type, partner_id, partner_percentage, active, satrack_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['placa'],
            $data['brand'],
            $data['model'],
            $data['category'],
            $data['model_year'],
            $data['color'],
            $data['serial'],
            $data['motor'],
            $data['chasis'],
            $data['displacement'],
            $data['max_load'],
            $data['city'],
            $data['department'],
            $data['country'],
            $data['exp_soat'],
            $data['exp_tecno'],
            $data['exp_policy'],
            $data['default_driver_id'],
            $data['ownership_type'],
            $data['partner_id'],
            $data['partner_percentage'],
            $data['active'],
            $data['satrack_id']
        ]);

        $id = $this->pdo->lastInsertId();
        Audit::log('CREATE', 'VEHICLE', $id, "Vehículo creado: " . $data['placa']);
        $this->redirect('vehicles.php', ['success' => 'saved']);
    }

    private function update($data)
    {
        $sql = "UPDATE vehicles SET 
            placa=?, brand=?, model=?, 
            category=?, model_year=?, color=?, 
            serial_number=?, motor_number=?, chasis_number=?, 
            displacement=?, max_load=?, 
            register_city=?, register_department=?, register_country=?, 
            expiry_soat=?, expiry_tecno=?, expiry_policy=?,
            default_driver_id=?,
            ownership_type=?, partner_id=?, partner_percentage=?,
            active=?, satrack_id=? 
            WHERE id=?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['placa'],
            $data['brand'],
            $data['model'],
            $data['category'],
            $data['model_year'],
            $data['color'],
            $data['serial'],
            $data['motor'],
            $data['chasis'],
            $data['displacement'],
            $data['max_load'],
            $data['city'],
            $data['department'],
            $data['country'],
            $data['exp_soat'],
            $data['exp_tecno'],
            $data['exp_policy'],
            $data['default_driver_id'],
            $data['ownership_type'],
            $data['partner_id'],
            $data['partner_percentage'],
            $data['active'],
            $data['satrack_id'],
            $data['id']
        ]);

        Audit::log('UPDATE', 'VEHICLE', $data['id'], "Vehículo actualizado: " . $data['placa']);
        $this->redirect('vehicles.php', ['success' => 'saved']);
    }
    /**
     * Checks for maintenance alerts based on Kms and Date logic.
     */
    public function getSystemMaintenanceAlerts()
    {
        $alerts = [];
        $today = date('Y-m-d');

        // 1. Check Kms Alerts
        // Get current vehicle kms from trips (max kms_end)
        // We use view_vehicle_availability logic or subquery
        $sqlKms = "SELECT ms.*, v.placa, 
                          va.last_kms as current_kms
                   FROM maintenance_schedules ms
                   JOIN vehicles v ON ms.vehicle_id = v.id
                   JOIN view_vehicle_availability va ON v.id = va.id
                   WHERE ms.active = 1 
                   AND (ms.next_service_kms - IFNULL(ms.warning_margin_kms, 500)) <= va.last_kms";

        $stmt = $this->pdo->query($sqlKms);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $kms_left = $row['next_service_kms'] - $row['current_kms'];
            $alerts[] = [
                'type' => 'maintenance',
                'subtype' => 'kms',
                'entity' => $row['placa'],
                'concept' => $row['task_name'],
                'priority' => $row['priority'],
                'val_current' => $row['current_kms'],
                'val_target' => $row['next_service_kms'],
                'kms_left' => $kms_left,
                'is_overdue' => $kms_left <= 0,
                'link' => 'vehicle_details.php?id=' . $row['vehicle_id'] . '#maintenance'
            ];
        }

        // 2. Check Date Alerts
        $sqlDate = "SELECT ms.*, v.placa 
                    FROM maintenance_schedules ms
                    JOIN vehicles v ON ms.vehicle_id = v.id
                    WHERE ms.active = 1 
                    AND ms.next_service_date IS NOT NULL 
                    AND ms.next_service_date <= DATE_ADD('$today', INTERVAL IFNULL(ms.warning_margin_days, 7) DAY)";

        $stmtDate = $this->pdo->query($sqlDate);
        while ($row = $stmtDate->fetch(PDO::FETCH_ASSOC)) {
            $days_left = (strtotime($row['next_service_date']) - strtotime($today)) / (60 * 60 * 24);
            $alerts[] = [
                'type' => 'maintenance',
                'subtype' => 'date',
                'entity' => $row['placa'],
                'concept' => $row['task_name'] . ' (Fecha)',
                'priority' => $row['priority'],
                'val_current' => $today,
                'val_target' => $row['next_service_date'],
                'days_left' => round($days_left),
                'is_overdue' => $days_left < 0,
                'link' => 'vehicle_details.php?id=' . $row['vehicle_id'] . '#maintenance'
            ];
        }

        // Sort by Criticality
        usort($alerts, function ($a, $b) {
            $aScore = $a['is_overdue'] ? 100 : 0;
            $bScore = $b['is_overdue'] ? 100 : 0;
            return $bScore <=> $aScore;
        });

        return $alerts;
    }

    public function destroy()
    {
        $this->requireRole('Admin');
        $id = $_GET['id'] ?? null;

        if (!$id)
            $this->redirect('vehicles.php');

        try {
            $this->pdo->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$id]);
            Audit::log('DELETE', 'VEHICLE', $id, "Eliminación de vehículo");
            $this->redirect('vehicles.php', ['success' => 'deleted']);
        } catch (PDOException $e) {
            $this->redirect('vehicles.php', ['error' => 'fk_constraint']);
        }
    }
}
?>