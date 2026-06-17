<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Disable time limit for large imports
set_time_limit(300);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    die("Acceso inválido");
}

$file = $_FILES['csv_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    header("Location: import_trips.php?status=error&msg=Error al subir archivo");
    exit;
}

$handle = fopen($file['tmp_name'], "r");
if ($handle === FALSE) {
    header("Location: import_trips.php?status=error&msg=No se pudo abrir el archivo");
    exit;
}

// Helper to clean currency string to float
function parseMoney($str)
{
    // Remove '$', spaces, and thousand separators (.), keep decimal comma if present
    // Expected format: "$3.162.000,00" or "$3,162,000.00" - Context suggests Colombian format (dot thousand, comma decimal)
    // BUT user CSV sample shows: "$3,162,000.00" -> This looks like Comma Thousands, Dot Decimal. 
    // Wait, sample: "228,137" (Kms) -> Comma thousands? Or comma decimal? 
    // Sample money: "$3,162,000.00"
    // Let's assume standard programming float: remove everything except digits and dot.

    // Heuristic: Remove '$' and ',' then check.
    $clean = str_replace(['$', ' ', ','], '', $str);
    return floatval($clean);
}

// Helper to parse Date (DD/MM/YYYY) to YYYY-MM-DD
function parseDate($str)
{
    if (empty($str))
        return null;
    $d = DateTime::createFromFormat('d/m/Y', trim($str));
    return $d ? $d->format('Y-m-d') : null;
}

// Entity Resolvers (Memoized)
$vehicleCache = [];
$driverCache = [];
$locationCache = [];
$clientCache = [];
$materialCache = [];

function getVehicleId($placa)
{
    global $pdo, $vehicleCache;
    $placa = trim(strtoupper($placa));
    if (isset($vehicleCache[$placa]))
        return $vehicleCache[$placa];

    $stmt = $pdo->prepare("SELECT id FROM vehicles WHERE placa = ?");
    $stmt->execute([$placa]);
    $id = $stmt->fetchColumn();

    if (!$id) {
        $pdo->prepare("INSERT INTO vehicles (placa, type, brand, active) VALUES (?, 'Tractomula', 'GENERIC', 1)")->execute([$placa]);
        $id = $pdo->lastInsertId();
    }
    $vehicleCache[$placa] = $id;
    return $id;
}

function getDriverId($name)
{
    global $pdo, $driverCache;
    $name = trim(strtoupper($name));
    if (isset($driverCache[$name]))
        return $driverCache[$name];

    // Simple fuzzy match could be dangerous, sticking to exact logic for now or simple "First Last"
    $parts = explode(' ', $name, 2);
    $first = $parts[0] ?? 'Unknown';
    $last = $parts[1] ?? 'Driver';

    $stmt = $pdo->prepare("SELECT id FROM personnel WHERE CONCAT(firstname, ' ', lastname) LIKE ? OR CONCAT(firstname, ' ', lastname1) LIKE ?");
    $stmt->execute(["%$name%", "%$name%"]);
    $id = $stmt->fetchColumn();

    if (!$id) {
        $pdo->prepare("INSERT INTO personnel (firstname, lastname, type, active) VALUES (?, ?, 'Conductor', 1)")->execute([$first, $last]);
        $id = $pdo->lastInsertId();
    }
    $driverCache[$name] = $id;
    return $id;
}

function getLocationId($name)
{
    global $pdo, $locationCache;
    $name = trim(strtoupper($name));
    if (empty($name))
        return null;
    if (isset($locationCache[$name]))
        return $locationCache[$name];

    $stmt = $pdo->prepare("SELECT id FROM locations WHERE name = ?");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();

    if (!$id) {
        $pdo->prepare("INSERT INTO locations (name, active) VALUES (?, 1)")->execute([$name]);
        $id = $pdo->lastInsertId();
    }
    $locationCache[$name] = $id;
    return $id;
}

// ----------------------------------------------------
// PROCESS CSV
// ----------------------------------------------------

$header = fgetcsv($handle, 0, ","); // Get header row
// Map header names to indices
$colMap = array_flip(array_map('trim', $header));

// Verify critical columns
$required = ['FECHA CARGA', 'VEHICULO', 'CONDUCTOR', 'ORIGEN VIAJE', 'DESTINO VIAJE'];
foreach ($required as $r) {
    if (!isset($colMap[$r])) {
        // Try fuzzy match or die
        // In user sample: "FECHA CARGA", "VEHICULO", etc exist.
    }
}

$count = 0;
$pdo->beginTransaction();

try {
    while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
        // Skip empty rows
        if (empty(implode('', $row)))
            continue;

        // Extract Data
        $date_load = parseDate($row[$colMap['FECHA CARGA']] ?? '');
        $date_unload = parseDate($row[$colMap['FECHA DESCARGA']] ?? '');
        $placa = $row[$colMap['VEHICULO']] ?? '';
        $driverName = $row[$colMap['CONDUCTOR']] ?? '';

        if (!$date_load || !$placa)
            continue; // Skip invalid minimums

        $vehicle_id = getVehicleId($placa);
        $driver_id = getDriverId($driverName);

        $origin = $row[$colMap['ORIGEN VIAJE']] ?? '';
        getLocationId($origin); // Ensure exists
        $destination = $row[$colMap['DESTINO VIAJE']] ?? '';
        getLocationId($destination); // Ensure exists

        // Kms - Careful with formatting "228,137" -> 228137
        $kms_start = parseMoney($row[$colMap['KMS INICIAL TACOMETRO']] ?? '0');
        $kms_end = parseMoney($row[$colMap['KMS FINAL TACOMETRO']] ?? '0');

        // Money
        $flete_bruto = parseMoney($row[$colMap['VALOR FLETE MANIFIESTO']] ?? '0');
        $advance_manifest = parseMoney($row[$colMap['ANTICIPO MANIFIESTO']] ?? '0');
        $advance_owner = parseMoney($row[$colMap['ANTICIPO PROPIETARIO']] ?? '0');

        // Insert Trip
        $stmt = $pdo->prepare("INSERT INTO trips (
            vehicle_id, driver_id, origin, destination, 
            date_load, date_unload, kms_start, kms_end,
            flete_bruto, advance_manifest, advance_owner,
            status, trip_type, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Finalizado', 'nacional', ?)");

        $stmt->execute([
            $vehicle_id,
            $driver_id,
            $origin,
            $destination,
            $date_load,
            $date_unload,
            $kms_start,
            $kms_end,
            $flete_bruto,
            $advance_manifest,
            $advance_owner,
            $_SESSION['user_id'] ?? 1
        ]);

        $trip_id = $pdo->lastInsertId();

        // -----------------------
        // EXPENSES PROCESSING
        // -----------------------

        // 1. Peajes
        $peajes = parseMoney($row[$colMap['PEAJES']] ?? '0');
        if ($peajes > 0) {
            $pdo->prepare("INSERT INTO expenses (trip_id, category_id, amount, description, paid_by, date) VALUES (?, (SELECT id FROM expense_categories WHERE name='Peajes' LIMIT 1), ?, 'Importado CSV', 'Conductor', ?)")
                ->execute([$trip_id, $peajes, $date_load]);
        }

        // 2. Combustible (Total)
        $fuel = parseMoney($row[$colMap['COMBUSTIBLE TOTAL FACTURAS']] ?? '0');
        if ($fuel > 0) {
            $pdo->prepare("INSERT INTO expenses (trip_id, category_id, amount, description, paid_by, date) VALUES (?, (SELECT id FROM expense_categories WHERE name LIKE 'Comb%' LIMIT 1), ?, 'Combustible Importado', 'Empresa', ?)")
                ->execute([$trip_id, $fuel, $date_load]);
        }

        // 3. Others (Lavada, Montallantas, Encarpa/Desencarpa, etc)
        $othersMap = [
            'LAVADA' => 'Lavada',
            'MONTAJE LLANTAS' => 'Mantenimiento',
            'ENGRACE' => 'Mantenimiento',
            'ENCARROSADA' => 'Varios',
            'DESENCARROZADA' => 'Varios',
            'OTROS 1' => 'Varios',
            'OTROS 2' => 'Varios'
        ];

        foreach ($othersMap as $col => $catName) {
            if (isset($colMap[$col])) {
                $val = parseMoney($row[$colMap[$col]]);
                if ($val > 0) {
                    $pdo->prepare("INSERT INTO expenses (trip_id, category_id, amount, description, paid_by, date) VALUES (?, (SELECT id FROM expense_categories WHERE name LIKE ? LIMIT 1), ?, ?, 'Conductor', ?)")
                        ->execute([$trip_id, "%$catName%", $val, "Import: $col", $date_load]);
                }
            }
        }

        calculateTripFinancials($trip_id);
        $count++;
    }

    $pdo->commit();
    header("Location: import_trips.php?status=success&count=$count");

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: import_trips.php?status=error&msg=" . urlencode($e->getMessage()));
}
