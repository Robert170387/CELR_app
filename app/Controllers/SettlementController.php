<?php
// app/Controllers/SettlementController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
require_once __DIR__ . '/../../includes/db.php';

use App\Core\Controller;
use App\Helpers\Audit;
use PDO;
use PDOException;

class SettlementController extends Controller
{

    /**
     * Handles creating a new settlement (formerly save_settlement.php)
     */
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid method']);
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            $this->jsonResponse(['success' => false, 'error' => 'No data provided']);
        }

        try {
            // 1. Uniqueness Check
            $stmtCheck = $this->pdo->prepare("SELECT id FROM settlements WHERE personnel_id = ? AND date_start = ? AND date_end = ?");
            $stmtCheck->execute([$data['personnel_id'], $data['date_start'], $data['date_end']]);

            if ($stmtCheck->fetchColumn()) {
                $this->jsonResponse(['success' => false, 'error' => 'Ya existe una liquidación para este conductor en el rango de fechas seleccionado.']);
            }

            // 2. Find Primary Vehicle
            $vehicle_id = $this->findPrimaryVehicle($data['personnel_id'], $data['date_start'], $data['date_end']);

            // 3. Insert Settlement
            $settlement_id = $this->insertSettlement($data);

            // 4. Create Automatic Expenses
            if ($vehicle_id) {
                $this->createAutomaticExpenses($vehicle_id, $settlement_id, $data);
            }

            // 5. Update Trips Status
            $this->updateTripsStatus($settlement_id, $data);

            // 6. Audit
            Audit::log('CREATE', 'SETTLEMENT', $settlement_id, "Liquidación creada del " . $data['date_start'] . " al " . $data['date_end']);

            $this->jsonResponse(['success' => true, 'settlement_id' => $settlement_id]);

        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Handles updating trip settlement status (formerly save_settlement_status.php)
     */
    public function updateTripStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('trips.php');
        }

        $trip_id = $_POST['trip_id'] ?? null;
        $action = $_POST['action'] ?? null;
        $notes = $_POST['notes'] ?? '';

        if (!$trip_id || !in_array($action, ['settle', 'reopen'])) {
            $this->redirect('trips.php', ['error' => 'invalid_request']);
        }

        try {
            $new_status = ($action === 'settle') ? 'Settled' : 'Pending';

            $sql = "UPDATE trips SET settlement_status = ?, settlement_notes = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$new_status, $notes, $trip_id]);

            Audit::log('UPDATE', 'TRIP_SETTLEMENT', $trip_id, "Status changed to $new_status. Notes: $notes");

            $this->redirect('trip_details.php', ['id' => $trip_id, 'success' => 'saved']);

        } catch (PDOException $e) {
            $this->redirect('trip_details.php', ['id' => $trip_id, 'error' => 'db_error']);
        }
    }

    private function findPrimaryVehicle($driverId, $start, $end)
    {
        $stmt = $this->pdo->prepare("
            SELECT vehicle_id, COUNT(*) as trip_count 
            FROM trips 
            WHERE driver_id = ? AND date_load BETWEEN ? AND ?
            GROUP BY vehicle_id 
            ORDER BY trip_count DESC 
            LIMIT 1
        ");
        $stmt->execute([$driverId, $start, $end]);
        return $stmt->fetchColumn();
    }

    private function insertSettlement($data)
    {
        $sql = "INSERT INTO settlements (
            personnel_id, date_start, date_end, month, year, salary_basic, transport_assistance, 
            total_commissions, total_advances, total_advances_manifest, total_advances_owner,
            total_expenses, balance_to_discount, partial_payment, net_to_pay, notes,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['personnel_id'],
            $data['date_start'],
            $data['date_end'],
            (int) date('m', strtotime($data['date_start'])),
            (int) date('Y', strtotime($data['date_start'])),
            $data['salary_basic'],
            $data['transport_assistance'],
            $data['total_commissions'],
            $data['total_advances'],
            $data['total_advances_manifest'] ?? 0,
            $data['total_advances_owner'] ?? 0,
            $data['total_expenses'],
            $data['balance_to_discount'],
            $data['partial_payment'] ?? 0,
            $data['net_to_pay'],
            $data['notes'] ?? '',
            $this->userId
        ]);

        return $this->pdo->lastInsertId();
    }

    private function createAutomaticExpenses($vehicleId, $settlementId, $data)
    {
        $creator = $this->userId ?? 1;
        $date_record = $data['date_end'];

        // Basic Salary
        if ($data['salary_basic'] > 0) {
            $stmt = $this->pdo->prepare("INSERT INTO expenses (date, category, paid_by, amount, description, vehicle_id, created_by) 
                                        VALUES (?, 'sueldo', 'Propietario', ?, ?, ?, ?)");
            $stmt->execute([
                $date_record,
                $data['salary_basic'],
                "Sueldo Básico - Liq #$settlementId (" . $data['date_start'] . " a " . $data['date_end'] . ")",
                $vehicleId,
                $creator
            ]);
        }

        // Transport Assistance
        if ($data['transport_assistance'] > 0) {
            $stmt = $this->pdo->prepare("INSERT INTO expenses (date, category, paid_by, amount, description, vehicle_id, created_by) 
                                           VALUES (?, 'auxilio_transporte', 'Propietario', ?, ?, ?, ?)");
            $stmt->execute([
                $date_record,
                $data['transport_assistance'],
                "Auxilio Transporte - Liq #$settlementId (" . $data['date_start'] . " a " . $data['date_end'] . ")",
                $vehicleId,
                $creator
            ]);
        }
    }

    private function updateTripsStatus($settlementId, $data)
    {
        $stmt = $this->pdo->prepare("
            UPDATE trips 
            SET settlement_status = 'Settled', 
                settlement_notes = ? 
            WHERE driver_id = ? AND date_load BETWEEN ? AND ?
        ");
        $stmt->execute([
            "Incluido en Liq #$settlementId",
            $data['personnel_id'],
            $data['date_start'],
            $data['date_end']
        ]);
    }

    public function destroy()
    {
        $this->requireRole('Admin');
        $id = $_GET['id'] ?? null;

        if (!$id)
            $this->redirect('settlements.php');

        try {
            // 1. Get details
            $stmtS = $this->pdo->prepare("SELECT personnel_id, date_start, date_end FROM settlements WHERE id = ?");
            $stmtS->execute([$id]);
            $settlement = $stmtS->fetch();

            if ($settlement) {
                // 2. Revert trips
                $stmtR = $this->pdo->prepare("
                    UPDATE trips 
                    SET settlement_status = 'Pending', 
                        settlement_notes = NULL 
                    WHERE driver_id = ? AND date_load BETWEEN ? AND ?
                ");
                $stmtR->execute([
                    $settlement['personnel_id'],
                    $settlement['date_start'],
                    $settlement['date_end']
                ]);

                // 3. Delete settlement record
                $stmt = $this->pdo->prepare("DELETE FROM settlements WHERE id = ?");
                $stmt->execute([$id]);

                Audit::log('DELETE', 'SETTLEMENT', $id, "Eliminación de liquidación y reversión de viajes");
            }

            $this->redirect('settlements.php', ['success' => 'deleted']);
        } catch (PDOException $e) {
            $this->redirect('settlements.php', ['error' => 'db_error']);
        }
    }
}
?>