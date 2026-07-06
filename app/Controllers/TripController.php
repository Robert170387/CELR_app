<?php
// app/Controllers/TripController.php

namespace App\Controllers;

require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/Audit.php';
// We assume includes/functions.php and db.php are available via the entry point (shim)
// OR we require them here to be safe.
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

use App\Core\Controller;
use App\Helpers\Audit;
use PDO;
use PDOException;

class TripController extends Controller
{

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die('Method Not Allowed');
        }

        // --- CSRF VALIDATION ---
        validateCsrfToken();

        // --- VALIDATION LAYER ---
        $required = [
            'trip_type' => 'string',
            'vehicle_id' => 'numeric',
            'driver_id' => 'numeric',
            // material_id and client_id are optional (form allows '-- Seleccione --')
            // origin and destination come from JS-populated hidden fields, validated separately
            'date_load' => 'date',
            'kms_start' => 'numeric',
            'flete_bruto' => 'numeric',
            'percent_rete_fuente' => 'numeric',
            'percent_rete_ica' => 'numeric',
            'advance_manifest' => 'numeric'
        ];

        $financials = [
            'flete_bruto',
            'weight_declared',
            'weight_origin',
            'weight_dest',
            'percent_rete_fuente',
            'percent_rete_ica',
            'percent_iva',
            'percent_rete_iva',
            'percent_deductible_3',
            'value_deductible_4',
            'value_deductible_5',
            'value_deductible_6',
            'advance_manifest',
            'advance_owner'
        ];

        // Accessing global functions for now to maintain compatibility
        $errors = array_merge(validatePOST($required), validateFinancials($financials));

        if (!empty($errors)) {
            setFlashMessage('error', 'Error de Validación', implode('<br>', $errors));
            $editParam = isset($_POST['trip_id']) && $_POST['trip_id'] ? ['edit' => $_POST['trip_id']] : [];
            $this->redirect('trip_create.php', $editParam);
        }

        // --- COLLECT INPUTS ---
        $data = $this->collectTripData();

        // --- LOGIC VALIDATION ---
        $this->validateBusinessLogic($data);

        // --- FILE UPLOAD ---
        $manifest_file_path = $this->handleFileUpload();

        // --- AVAILABILITY CHECK ---
        $this->checkAvailability($data['vehicle_id'], $data['driver_id'], $data['status'], $data['trip_id']);

        try {
            // Start transaction for financial integrity
            global $pdo;
            $pdo->beginTransaction();

            $redirectUrl = '';
            if ($data['trip_id']) {
                $redirectUrl = $this->updateTrip($data, $manifest_file_path);
            } else {
                $redirectUrl = $this->createTrip($data, $manifest_file_path);
            }

            // Commit transaction
            $pdo->commit();
            
            // Perform redirect after successful commit
            if ($redirectUrl) {
                header("Location: $redirectUrl");
                exit;
            }

        } catch (PDOException $e) {
            // Rollback on error
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Error saving trip: " . $e->getMessage());

            $errorMsg = isProduction() ? "Error al guardar el viaje. Por favor intente nuevamente." : "Error saving trip: " . $e->getMessage();
            setFlashMessage('error', 'Error de Base de Datos', $errorMsg);
            $editParam = isset($_POST['trip_id']) && $_POST['trip_id'] ? ['edit' => $_POST['trip_id']] : [];
            $this->redirect('trip_create.php', $editParam);
        }
    }

    private function collectTripData()
    {
        // Map settlement_status from form value to DB enum
        $settlementMap = ['Settled' => 'Complete', 'Pending' => 'Pending', 'Partial' => 'Partial', 'Cancelled' => 'Cancelled', 'Complete' => 'Complete'];
        $rawSettlement = $_POST['settlement_status'] ?? 'Pending';
        $settlement = $settlementMap[$rawSettlement] ?? 'Pending';

        // Build origin/destination from hidden fields or city names
        $origin = !empty($_POST['origin']) ? $_POST['origin'] : '';
        $destination = !empty($_POST['destination']) ? $_POST['destination'] : '';

        return [
            'trip_type' => $_POST['trip_type'],
            'status' => $_POST['status'] ?? 'En Progreso',
            'vehicle_id' => $_POST['vehicle_id'],
            'driver_id' => $_POST['driver_id'],
            'material_id' => !empty($_POST['material_id']) ? $_POST['material_id'] : null,
            'client_id' => !empty($_POST['client_id']) ? $_POST['client_id'] : null,

            'origin' => $origin,
            'origin_city_id' => !empty($_POST['origin_city_id']) ? $_POST['origin_city_id'] : null,
            'origin_state_id' => !empty($_POST['origin_state_id']) ? $_POST['origin_state_id'] : null,

            'destination' => $destination,
            'destination_city_id' => !empty($_POST['destination_city_id']) ? $_POST['destination_city_id'] : null,
            'destination_state_id' => !empty($_POST['destination_state_id']) ? $_POST['destination_state_id'] : null,

            'date_load' => $_POST['date_load'],
            'date_unload' => !empty($_POST['date_unload']) ? $_POST['date_unload'] : null,
            'manifest_date' => !empty($_POST['manifest_date']) ? $_POST['manifest_date'] : null,

            'kms_start' => $_POST['kms_start'],
            'kms_end' => !empty($_POST['kms_end']) ? $_POST['kms_end'] : $_POST['kms_start'],

            'manifest_company_id' => !empty($_POST['manifest_company_id']) ? $_POST['manifest_company_id'] : null,
            'manifest_number' => $_POST['manifest_number'] ?? '',
            'flete_bruto' => $_POST['flete_bruto'],

            'weight_declared' => $_POST['weight_declared'] ?? 0,
            'weight_origin' => $_POST['weight_origin'] ?? 0,
            'weight_dest' => $_POST['weight_dest'] ?? 0,

            'percent_rf' => $_POST['percent_rete_fuente'],
            'percent_ica' => $_POST['percent_rete_ica'],
            'percent_iva' => $_POST['percent_iva'] ?? 0,
            'percent_rete_iva' => $_POST['percent_rete_iva'] ?? 0,
            'percent_d3' => $_POST['percent_deductible_3'] ?? 0,

            'val_d4' => !empty($_POST['value_deductible_4']) ? $_POST['value_deductible_4'] : 0,
            'val_d5' => !empty($_POST['value_deductible_5']) ? $_POST['value_deductible_5'] : 0,
            'val_d6' => !empty($_POST['value_deductible_6']) ? $_POST['value_deductible_6'] : 0,

            'advance' => $_POST['advance_manifest'],
            'advance_manifest_to_driver' => isset($_POST['advance_manifest_to_driver']) ? 1 : 0,
            'advance_owner' => !empty($_POST['advance_owner']) ? $_POST['advance_owner'] : 0,
            'advance_owner_resp' => $_POST['advance_owner_responsible'] ?? '',

            'origin_point_id' => !empty($_POST['origin_point_id']) ? $_POST['origin_point_id'] : null,
            'destination_point_id' => !empty($_POST['destination_point_id']) ? $_POST['destination_point_id'] : null,

            'trip_id' => $_POST['trip_id'] ?? null,
            'settlement_status' => $settlement,
            'settlement_notes' => $_POST['settlement_notes'] ?? '',

            'odometer_confirmed' => isset($_POST['odometer_confirmed']) ? 1 : 0
        ];
    }

    private function validateBusinessLogic($data)
    {
        // Validation: Prevent Finalizado if not Settled (DB enum uses 'Complete')
        if ($data['status'] === 'Finalizado' && $data['settlement_status'] !== 'Complete') {
            setFlashMessage('error', 'Error de Liquidación', 'No se puede finalizar el viaje sin liquidar el saldo de viáticos.');
            $editParam = $data['trip_id'] ? ['edit' => $data['trip_id']] : [];
            $this->redirect('trip_create.php', $editParam);
        }

        // 1. Date Logic
        if ($data['date_load'] && $data['date_unload'] && $data['date_unload'] < $data['date_load']) {
            $this->redirect('trip_create.php', ['error' => 'date_logic', 'edit' => $data['trip_id']]);
        }

        // 2. Odometer Logic
        if ($data['kms_end'] > 0 && $data['kms_end'] < $data['kms_start'] && !$data['odometer_confirmed']) {
            $this->redirect('trip_create.php', ['error' => 'odometer_logic', 'edit' => $data['trip_id']]);
        }
    }

    private function handleFileUpload()
    {
        $manifest_file_path = null;
        if (isset($_FILES['manifest_file']) && $_FILES['manifest_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/manifests/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['manifest_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'manifest_' . time() . '_' . uniqid() . '.' . $file_ext;
            $manifest_file_path = $upload_dir . $file_name;
            move_uploaded_file($_FILES['manifest_file']['tmp_name'], $manifest_file_path);
        }
        return $manifest_file_path;
    }

    private function checkAvailability($vehicleId, $driverId, $status, $tripId)
    {
        $busyCheckSql = "SELECT id, 'Vehículo' as type FROM trips WHERE vehicle_id = ? AND status = 'En Progreso' AND id != ?
                         UNION
                         SELECT id, 'Conductor' as type FROM trips WHERE driver_id = ? AND status = 'En Progreso' AND id != ?";
        $busyStmt = $this->pdo->prepare($busyCheckSql);
        $busyStmt->execute([$vehicleId, $tripId ?? 0, $driverId, $tripId ?? 0]);
        $busyResult = $busyStmt->fetch();

        if ($busyResult && $status === 'En Progreso') {
            setFlashMessage('error', 'Conflicto de Disponibilidad', "El " . $busyResult['type'] . " ya tiene un viaje activo 'En Progreso'.");
            $editParam = $tripId ? ['edit' => $tripId] : [];
            $this->redirect('trip_create.php', $editParam);
        }
    }

    private function updateTrip($data, $filePath)
    {
        if (!$filePath) {
            $stmtOld = $this->pdo->prepare("SELECT manifest_file FROM trips WHERE id = ?");
            $stmtOld->execute([$data['trip_id']]);
            $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);
            $filePath = $oldData['manifest_file'];
        }

        $sql = "UPDATE trips SET
                trip_type = ?, status = ?, vehicle_id = ?, driver_id = ?, client_id = ?, material_id = ?,
                origin_point_id = ?, destination_point_id = ?,
                origin = ?, origin_city_id = ?, origin_state_id = ?,
                destination = ?, destination_city_id = ?, destination_state_id = ?,
                date_load = ?, date_unload = ?,
                manifest_date = ?,
                kms_start = ?, kms_end = ?,
                manifest_company_id = ?, manifest_number = ?, flete_bruto = ?,
                weight_declared = ?, weight_origin = ?, weight_dest = ?,
                percent_iva = ?, percent_rete_iva = ?,
                percent_rete_fuente = ?, percent_rete_ica = ?, 
                percent_deductible_3 = ?, 
                value_deductible_4 = ?, value_deductible_5 = ?, value_deductible_6 = ?,
                advance_manifest = ?, advance_manifest_to_driver = ?, advance_owner = ?, advance_owner_responsible = ?, 
                settlement_status = ?, settlement_notes = ?, manifest_file = ?
                WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['trip_type'],
            $data['status'],
            $data['vehicle_id'],
            $data['driver_id'],
            $data['client_id'],
            $data['material_id'],
            $data['origin_point_id'],
            $data['destination_point_id'],
            $data['origin'],
            $data['origin_city_id'],
            $data['origin_state_id'],
            $data['destination'],
            $data['destination_city_id'],
            $data['destination_state_id'],
            $data['date_load'],
            $data['date_unload'],
            $data['manifest_date'],
            $data['kms_start'],
            $data['kms_end'],
            $data['manifest_company_id'],
            $data['manifest_number'],
            $data['flete_bruto'],
            $data['weight_declared'],
            $data['weight_origin'],
            $data['weight_dest'],
            $data['percent_iva'],
            $data['percent_rete_iva'],
            $data['percent_rf'],
            $data['percent_ica'],
            $data['percent_d3'],
            $data['val_d4'],
            $data['val_d5'],
            $data['val_d6'],
            $data['advance'],
            $data['advance_manifest_to_driver'],
            $data['advance_owner'],
            $data['advance_owner_resp'],
            $data['settlement_status'],
            $data['settlement_notes'],
            $filePath,
            $data['trip_id']
        ]);

        calculateTripFinancials($data['trip_id']);

        // Sync RNDC manifest record (create if missing, update if exists)
        if (!empty($data['manifest_number'])) {
            try {
                $stmtCheck = $this->pdo->prepare("SELECT id FROM manifiestos_rndc WHERE trip_id = ?");
                $stmtCheck->execute([$data['trip_id']]);
                $existingRndcId = $stmtCheck->fetchColumn();

                // Look up client name
                $clientName = '';
                if (!empty($data['client_id'])) {
                    $stmtClient = $this->pdo->prepare("SELECT 
                        CASE WHEN person_type = 'Jurídica' THEN business_name 
                        ELSE CONCAT(firstname, ' ', lastname1) END as name 
                        FROM clients WHERE id = ?");
                    $stmtClient->execute([$data['client_id']]);
                    $clientName = $stmtClient->fetchColumn() ?: '';
                }

                // Look up material name
                $materialName = '';
                if (!empty($data['material_id'])) {
                    $stmtMat = $this->pdo->prepare("SELECT name FROM materials WHERE id = ?");
                    $stmtMat->execute([$data['material_id']]);
                    $materialName = $stmtMat->fetchColumn() ?: '';
                }

                // Look up manifest company
                $empresaTransporte = '';
                if (!empty($data['manifest_company_id'])) {
                    $stmtEmp = $this->pdo->prepare("SELECT name FROM manifest_companies WHERE id = ?");
                    $stmtEmp->execute([$data['manifest_company_id']]);
                    $empresaTransporte = $stmtEmp->fetchColumn() ?: '';
                }

                if ($existingRndcId) {
                    $stmtUpd = $this->pdo->prepare("UPDATE manifiestos_rndc SET
                        nro_manifiesto = ?, vehicle_id = ?, driver_id = ?,
                        origen = ?, destino = ?, descripcion_mercancia = ?, peso_kg = ?,
                        flete_pactado = ?, anticipo = ?, fecha_expedicion = ?,
                        empresa_transporte = ?, remitente_nombre = ?
                        WHERE id = ?");
                    $stmtUpd->execute([
                        $data['manifest_number'], $data['vehicle_id'], $data['driver_id'],
                        $data['origin'], $data['destination'], $materialName,
                        $data['weight_declared'] ?: null, $data['flete_bruto'],
                        $data['advance'], $data['date_load'],
                        $empresaTransporte, $clientName,
                        $existingRndcId
                    ]);
                } else {
                    $stmtIns = $this->pdo->prepare("INSERT INTO manifiestos_rndc (
                        nro_manifiesto, vehicle_id, driver_id, trip_id,
                        origen, destino, descripcion_mercancia, peso_kg,
                        flete_pactado, anticipo, fecha_expedicion,
                        empresa_transporte, remitente_nombre, estado
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activo')");
                    $stmtIns->execute([
                        $data['manifest_number'], $data['vehicle_id'], $data['driver_id'],
                        $data['trip_id'],
                        $data['origin'], $data['destination'], $materialName,
                        $data['weight_declared'] ?: null, $data['flete_bruto'],
                        $data['advance'], $data['date_load'],
                        $empresaTransporte, $clientName
                    ]);
                }
            } catch (PDOException $e) {
                error_log("Error syncing RNDC manifest for trip {$data['trip_id']}: " . $e->getMessage());
            }
        }

        // Trigger alert if status is 'Entregado'
        if ($data['status'] === 'Entregado') {
            $this->createSystemAlert(
                'status_change',
                $data['trip_id'],
                'TRIP',
                "Viaje en Destino",
                "El viaje #{$data['trip_id']} ha reportado llegada a destino. Pendiente por liquidar.",
                'normal'
            );
        }

        Audit::log('UPDATE', 'TRIP', $data['trip_id'], "Actualización información viaje");
        return 'trip_create.php?edit=' . $data['trip_id'] . '&msg=success';
    }

    public function createTrip($data, $filePath)
    {
        $created_by = $_SESSION['user_id'];

        $sql = "INSERT INTO trips (
            trip_type, status, vehicle_id, driver_id, client_id, material_id,
            origin_point_id, destination_point_id,
            origin, origin_city_id, origin_state_id,
            destination, destination_city_id, destination_state_id,
            date_load, date_unload,
            manifest_date,
            kms_start, kms_end,
            manifest_company_id, manifest_number, flete_bruto,
            weight_declared, weight_origin, weight_dest,
            percent_iva, percent_rete_iva,
            percent_rete_fuente, percent_rete_ica,
            percent_deductible_3,
            value_deductible_4, value_deductible_5, value_deductible_6,
            advance_manifest, advance_manifest_to_driver, advance_owner, advance_owner_responsible, created_by,
            settlement_status, settlement_notes, manifest_file
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?,
            ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?,
            ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['trip_type'],
            $data['status'],
            $data['vehicle_id'],
            $data['driver_id'],
            $data['client_id'],
            $data['material_id'],
            $data['origin_point_id'],
            $data['destination_point_id'],
            $data['origin'],
            $data['origin_city_id'],
            $data['origin_state_id'],
            $data['destination'],
            $data['destination_city_id'],
            $data['destination_state_id'],
            $data['date_load'],
            $data['date_unload'],
            $data['kms_start'],
            $data['kms_end'],
            $data['manifest_company_id'],
            $data['manifest_number'],
            $data['flete_bruto'],
            $data['weight_declared'],
            $data['weight_origin'],
            $data['weight_dest'],
            $data['percent_iva'],
            $data['percent_rete_iva'],
            $data['percent_rf'],
            $data['percent_ica'],
            $data['percent_d3'],
            $data['val_d4'],
            $data['val_d5'],
            $data['val_d6'],
            $data['advance'],
            $data['advance_manifest_to_driver'],
            $data['advance_owner'],
            $data['advance_owner_resp'],
            $created_by,
            $data['settlement_status'],
            $data['settlement_notes'],
            $filePath
        ]);

        $trip_id = $this->pdo->lastInsertId();
        calculateTripFinancials($trip_id);

        // Auto-create RNDC manifest record if manifest_number is present
        if (!empty($data['manifest_number'])) {
            try {
                // Look up client name for remitente
                $clientName = '';
                if (!empty($data['client_id'])) {
                    $stmtClient = $this->pdo->prepare("SELECT 
                        CASE WHEN person_type = 'Jurídica' THEN business_name 
                        ELSE CONCAT(firstname, ' ', lastname1) END as name 
                        FROM clients WHERE id = ?");
                    $stmtClient->execute([$data['client_id']]);
                    $clientName = $stmtClient->fetchColumn() ?: '';
                }

                // Look up material name for description
                $materialName = '';
                if (!empty($data['material_id'])) {
                    $stmtMat = $this->pdo->prepare("SELECT name FROM materials WHERE id = ?");
                    $stmtMat->execute([$data['material_id']]);
                    $materialName = $stmtMat->fetchColumn() ?: '';
                }

                // Look up manifest company for empresa_transporte
                $empresaTransporte = '';
                if (!empty($data['manifest_company_id'])) {
                    $stmtEmp = $this->pdo->prepare("SELECT name FROM manifest_companies WHERE id = ?");
                    $stmtEmp->execute([$data['manifest_company_id']]);
                    $empresaTransporte = $stmtEmp->fetchColumn() ?: '';
                }

                $stmtRndc = $this->pdo->prepare("INSERT INTO manifiestos_rndc (
                    nro_manifiesto, vehicle_id, driver_id, trip_id,
                    origen, destino, descripcion_mercancia, peso_kg,
                    flete_pactado, anticipo, fecha_expedicion,
                    empresa_transporte, remitente_nombre,
                    estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activo')");

                $stmtRndc->execute([
                    $data['manifest_number'],
                    $data['vehicle_id'],
                    $data['driver_id'],
                    $trip_id,
                    $data['origin'],
                    $data['destination'],
                    $materialName,
                    $data['weight_declared'] ?: null,
                    $data['flete_bruto'],
                    $data['advance'],
                    $data['date_load'],
                    $empresaTransporte,
                    $clientName
                ]);
            } catch (PDOException $e) {
                error_log("Error auto-creating RNDC manifest for trip $trip_id: " . $e->getMessage());
            }
        }

        Audit::log('CREATE', 'TRIP', $trip_id, "Registro de nuevo viaje");
        return 'trip_details.php?id=' . $trip_id . '&created=1&clear_draft=tripForm';
    }

    public function uploadPod()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('error', 'Error', 'Método No Permitido');
            $this->redirect('trips.php');
        }

        $tripId = $_POST['trip_id'] ?? null;
        if (!$tripId) {
            setFlashMessage('error', 'Error', 'Trip ID requerido');
            $this->redirect('trips.php');
        }

        if (isset($_FILES['pod_file']) && $_FILES['pod_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/proofs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['pod_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'pod_' . $tripId . '_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['pod_file']['tmp_name'], $target_path)) {
                $stmt = $this->pdo->prepare("UPDATE trips SET delivery_proof_url = ? WHERE id = ?");
                $stmt->execute([$target_path, $tripId]);

                Audit::log('UPDATE', 'TRIP', $tripId, "Carga de prueba de entrega (ePOD)");
                $this->redirect('trip_details.php', ['id' => $tripId, 'success' => 'pod_uploaded']);
            } else {
                setFlashMessage('error', 'Error', 'Error al cargar el archivo en el servidor.');
                $this->redirect('trip_details.php', ['id' => $tripId]);
            }
        } else {
            $this->redirect('trip_details.php', ['id' => $tripId, 'error' => 'upload_failed']);
        }
    }
    public function destroy()
    {
        $this->requireRole('Admin');
        $id = $_GET['id'] ?? null;

        if (!$id)
            $this->redirect('trips.php');

        try {
            $this->pdo->prepare("DELETE FROM trips WHERE id = ?")->execute([$id]);
            Audit::log('DELETE', 'TRIP', $id, "Eliminación de viaje");
            $this->redirect('trips.php', ['success' => 'deleted']);
        } catch (PDOException $e) {
            $this->redirect('trips.php', ['error' => 'fk_constraint']);
        }
    }

    public function getPortalData($role, $linkedId)
    {
        $trips = [];
        $summary = ['total' => 0, 'active' => 0, 'completed' => 0];

        if ($role === 'cliente') {
            $stmt = $this->pdo->prepare("
                SELECT t.*, v.placa, p.firstname as driver_name 
                FROM trips t 
                JOIN vehicles v ON t.vehicle_id = v.id 
                JOIN personnel p ON t.driver_id = p.id
                WHERE t.client_id = ? 
                ORDER BY t.date_load DESC 
                LIMIT 20
            ");
            $stmt->execute([$linkedId]);
            $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $summary['total'] = $this->pdo->prepare("SELECT COUNT(*) FROM trips WHERE client_id = ?")->execute([$linkedId]) ? $this->pdo->query("SELECT COUNT(*) FROM trips WHERE client_id = $linkedId")->fetchColumn() : 0;
            $summary['active'] = $this->pdo->query("SELECT COUNT(*) FROM trips WHERE client_id = $linkedId AND status = 'En Progreso'")->fetchColumn();
            $summary['completed'] = $summary['total'] - $summary['active'];

        } elseif ($role === 'conductor') {
            $stmt = $this->pdo->prepare("
                SELECT t.*, v.placa, 
                       CASE 
                           WHEN c.person_type = 'Jurídica' THEN c.business_name 
                           ELSE CONCAT(c.firstname, ' ', c.lastname1) 
                       END as client_name
                FROM trips t 
                JOIN vehicles v ON t.vehicle_id = v.id 
                LEFT JOIN clients c ON t.client_id = c.id
                WHERE t.driver_id = ? 
                ORDER BY t.date_load DESC 
                LIMIT 20
            ");
            $stmt->execute([$linkedId]);
            $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $summary['total'] = $this->pdo->query("SELECT COUNT(*) FROM trips WHERE driver_id = $linkedId")->fetchColumn();
            $summary['active'] = $this->pdo->query("SELECT COUNT(*) FROM trips WHERE driver_id = $linkedId AND status = 'En Progreso'")->fetchColumn();
            $summary['completed'] = $summary['total'] - $summary['active'];
        }

        return ['trips' => $trips, 'summary' => $summary];
    }

    public function processDriverReport($tripId, $data)
    {
        $sql = "UPDATE trips SET 
                date_load = ?, 
                date_unload = ?, 
                kms_start = ?, 
                kms_end = ?, 
                origin = ?,
                origin_city_id = ?,
                origin_state_id = ?,
                destination = ?,
                destination_city_id = ?,
                destination_state_id = ?,
                advance_manifest = ?,
                advance_owner = ?,
                status = ?
                WHERE id = ?";

        $this->pdo->prepare($sql)->execute([
            $data['date_load'],
            $data['date_unload'],
            $data['kms_start'],
            $data['kms_end'],
            $data['origin'] ?? null,
            $data['origin_city_id'] ?? null,
            $data['origin_state_id'] ?? null,
            $data['destination'] ?? null,
            $data['destination_city_id'] ?? null,
            $data['destination_state_id'] ?? null,
            $data['advance_manifest'] ?? 0,
            $data['advance_owner'] ?? 0,
            $data['status'] ?? 'En Progreso',
            $tripId
        ]);

        calculateTripFinancials($tripId);

        $status = $data['status'] ?? 'En Progreso';
        Audit::log('UPDATE', 'TRIP', $tripId, "Reporte operativo guardado (Estado: $status)");
    }

    public function savePortalDigitalSheet()
    {
        $this->requireRole(['conductor', 'admin']);

        $trip_id = $_POST['trip_id'] ?? null;
        if (!$trip_id) {
            setFlashMessage('error', 'Error', 'Trip ID requerido.');
            $this->redirect('dashboard.php');
        }

        // Validate Ownership
        if ($_SESSION['role'] === 'conductor') {
            $stmtUser = $this->pdo->prepare("SELECT related_personnel_id FROM users WHERE id = ?");
            $stmtUser->execute([$_SESSION['user_id']]);
            $conductorId = $stmtUser->fetchColumn();

            $stmtTrip = $this->pdo->prepare("SELECT driver_id FROM trips WHERE id = ?");
            $stmtTrip->execute([$trip_id]);
            if ($stmtTrip->fetchColumn() != $conductorId) {
                setFlashMessage('error', 'No Autorizado', 'Este viaje no está asignado a tu usuario.');
                $this->redirect('dashboard.php');
            }
        }

        // Logic Validation
        $kms_start = $_POST['kms_start'] ?? 0;
        $kms_end = $_POST['kms_end'] ?? 0;
        if ($kms_end > 0 && $kms_end < $kms_start) {
            $this->redirect('dashboard.php', ['error' => 'kms_logic', 'odt' => $trip_id]);
        }

        try {
            $this->processDriverReport($trip_id, $_POST);

            Audit::log('UPDATE', 'TRIP', $trip_id, "Sección Operación: Planilla Digital 2025 actualizada");

            // Process Expenses if any
            if (isset($_POST['expenses'])) {
                $expenseCtrl = new \App\Controllers\ExpenseController();
                $expenseCtrl->storeMultipleFromPortal($trip_id, $_POST['expenses']);
            }

            $this->redirect('dashboard.php', ['success' => 'updated', 'odt' => $trip_id]);

        } catch (PDOException $e) {
            setFlashMessage('error', 'Error del Sistema', 'Error al procesar la planilla digital: ' . $e->getMessage());
            $this->redirect('dashboard.php', $trip_id ? ['odt' => $trip_id] : []);
        }
    }

    public function processBulkPaste()
    {
        $this->requireRole('admin');

        $input = file_get_contents('php://input');
        $payload = json_decode($input, true);

        if (!$payload || !isset($payload['data'])) {
            $this->jsonResponse(['success' => false, 'message' => 'No se recibieron datos válidos.']);
        }

        $rows = $payload['data'];
        $importedCount = 0;
        $errors = [];

        // Helpers
        $cleanMoney = function ($val) {
            if (!$val)
                return 0;
            return (float) preg_replace('/[^\d.]/', '', str_replace(',', '', $val));
        };
        $formatDate = function ($val) {
            if (!$val)
                return null;
            $val = trim($val);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val))
                return $val;
            $parts = explode('/', $val);
            if (count($parts) === 3) {
                $y = (strlen($parts[2]) == 2) ? '20' . $parts[2] : $parts[2];
                return sprintf("%04d-%02d-%02d", $y, $parts[1], $parts[0]);
            }
            return null;
        };

        try {
            $this->pdo->beginTransaction();

            foreach ($rows as $index => $row) {
                $placa = strtoupper(trim($row['placa'] ?? ''));
                $fechaRaw = trim($row['fecha'] ?? '');
                $manifiesto = trim($row['manifiesto'] ?? '');
                $origen = trim($row['origen'] ?? '');
                $destino = trim($row['destino'] ?? '');
                $fleteBruto = $cleanMoney($row['flete_bruto'] ?? 0);
                $fleteNeto = $cleanMoney($row['flete_neto'] ?? 0);
                $clientId = (int) ($row['client_id'] ?? 0);
                $fecha = $formatDate($fechaRaw);

                if (empty($placa) || empty($fecha)) {
                    $errors[] = "Fila #" . ($index + 1) . ": Placa ($placa) o Fecha ($fechaRaw) inválida.";
                    continue;
                }

                // Lookup Vehicle
                $stmtVeh = $this->pdo->prepare("SELECT id FROM vehicles WHERE placa = ? LIMIT 1");
                $stmtVeh->execute([$placa]);
                $vehicleId = $stmtVeh->fetchColumn();

                if (!$vehicleId) {
                    $errors[] = "Fila #" . ($index + 1) . ": La placa '$placa' no existe.";
                    continue;
                }

                if (!empty($origen)) {
                    $stmtC = $this->pdo->prepare("SELECT name FROM loc_cities WHERE name = ? LIMIT 1");
                    $stmtC->execute([$origen]);
                    if (!$stmtC->fetchColumn()) {
                        $errors[] = "Fila #" . ($index + 1) . ": Ciudad '$origen' no encontrada.";
                        continue;
                    }
                }

                // Insert Trip
                $sql = "INSERT INTO trips (
                    vehicle_id, client_id, origin, destination, manifest_number, 
                    date_load, flete_bruto, flete_neto, status, trip_type, created_at, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Finalizado', 'Nacional', NOW(), ?)";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $vehicleId,
                    $clientId ?: null,
                    $origen,
                    $destino,
                    $manifiesto,
                    $fecha,
                    $fleteBruto,
                    $fleteNeto,
                    $_SESSION['user_id']
                ]);

                $tripId = $this->pdo->lastInsertId();
                calculateTripFinancials($tripId);
                $importedCount++;
            }

            if (!empty($errors)) {
                if ($this->pdo->inTransaction())
                    $this->pdo->rollBack();
                $this->jsonResponse(['success' => false, 'message' => 'Se encontraron errores de validación. La importación fue cancelada para proteger la integridad de los datos.', 'errors' => $errors]);
            }

            $this->pdo->commit();

            if ($importedCount > 0) {
                Audit::log('CREATE', 'TRIP', 0, "Importación masiva: $importedCount viajes históricos creados.");
            }

            $this->jsonResponse([
                'success' => true,
                'message' => "Se importaron $importedCount registros correctamente.",
                'errors' => $errors
            ]);

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction())
                $this->pdo->rollBack();
            $this->jsonResponse(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
        }
    }
}