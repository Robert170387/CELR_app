<?php
include 'includes/db.php';
include 'includes/functions.php';
include 'includes/header.php';
require_once 'includes/SimpleXLSX.php';
require_once 'includes/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xlsx_file'])) {
    $file = $_FILES['xlsx_file']['tmp_name'];

    if (empty($file)) {
        $error = "Por favor seleccione un archivo.";
    } else {
        if ($xlsx = SimpleXLSX::parse($file)) {
            $rows = $xlsx->rows();
            $headers = array_shift($rows); // Remove headers

            $rowCount = 0;
            $successCount = 0;
            $errors = [];

            try {
                $pdo->beginTransaction();

                foreach ($rows as $data) {
                    $rowCount++;

                    // Skip if all columns are empty
                    if (empty(array_filter($data)))
                        continue;

                    if (count($data) < 12) {
                        $errors[] = "Fila $rowCount: Columnas insuficientes.";
                        continue;
                    }

                    $date_load = $data[0];
                    $trip_type = strtolower($data[1]);
                    $placa = strtoupper(trim($data[2]));
                    $driver_doc = trim($data[3]);
                    $client_name = trim($data[4]);
                    $material_name = trim($data[5]);
                    $origin = $data[6];
                    $destination = $data[7];
                    $manifest_number = $data[8];
                    $flete_bruto = floatval($data[9]);
                    $adv_manifest = floatval($data[10]);
                    $adv_owner = floatval($data[11]);

                    // 1. Find Vehicle ID
                    $stmtVeh = $pdo->prepare("SELECT id FROM vehicles WHERE placa = ? LIMIT 1");
                    $stmtVeh->execute([$placa]);
                    $vehicle_id = $stmtVeh->fetchColumn();

                    if (!$vehicle_id) {
                        $errors[] = "Fila $rowCount: Vehículo con placa '$placa' no encontrado.";
                        continue;
                    }

                    // 2. Find Driver ID by Document
                    $stmtDrv = $pdo->prepare("SELECT id FROM personnel WHERE document_number = ? AND type = 'Conductor' LIMIT 1");
                    $stmtDrv->execute([$driver_doc]);
                    $driver_id = $stmtDrv->fetchColumn();

                    if (!$driver_id) {
                        $errors[] = "Fila $rowCount: Conductor con documento '$driver_doc' no encontrado.";
                        continue;
                    }

                    // 3. Find Client ID
                    $stmtCli = $pdo->prepare("SELECT id FROM clients WHERE business_name = ? OR CONCAT(firstname, ' ', lastname1) = ? LIMIT 1");
                    $stmtCli->execute([$client_name, $client_name]);
                    $client_id = $stmtCli->fetchColumn();

                    // 4. Find Material ID
                    $stmtMat = $pdo->prepare("SELECT id FROM materials WHERE name = ? LIMIT 1");
                    $stmtMat->execute([$material_name]);
                    $material_id = $stmtMat->fetchColumn();

                    // Insert Trip
                    $sql = "INSERT INTO trips (
                                date_load, trip_type, vehicle_id, driver_id, client_id, material_id, 
                                origin, destination, manifest_number, flete_bruto, 
                                advance_manifest, advance_owner, status, settlement_status
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'En Progreso', 'Pending')";

                    $stmtIns = $pdo->prepare($sql);
                    $stmtIns->execute([
                        $date_load,
                        $trip_type,
                        $vehicle_id,
                        $driver_id,
                        $client_id,
                        $material_id,
                        $origin,
                        $destination,
                        $manifest_number,
                        $flete_bruto,
                        $adv_manifest,
                        $adv_owner
                    ]);

                    $newTripId = $pdo->lastInsertId();

                    // Recalculate financials (commissions, etc.)
                    calculateTripFinancials($newTripId);

                    $successCount++;
                }

                if (empty($errors)) {
                    $pdo->commit();
                    $message = "Importación exitosa. Se crearon $successCount viajes.";
                } else {
                    $pdo->rollBack();
                    $error = "Se encontraron errores. No se guardó nada.";
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error crítico: " . $e->getMessage();
            }
        } else {
            $error = SimpleXLSX::parseError();
        }
    }
}

// Download Template Logic
if (isset($_GET['download_template'])) {
    $header = ['Fecha (AAAA-MM-DD)', 'Tipo (urbano/nacional)', 'Placa', 'Doc Conductor', 'Nombre Cliente', 'Nombre Material', 'Origen', 'Destino', 'Nro Manifiesto', 'Flete Bruto', 'Anticipo Manifiesto', 'Anticipo Prop'];
    $example = ['2026-01-13', 'nacional', 'SKN756', '12345678', 'CLIENTE EJEMPLO SAS', 'CARBON', 'BARRANQUILLA', 'BOGOTA', 'MAN-1001', '3500000', '1500000', '500000'];

    $xlsx = SimpleXLSXGen::fromArray([$header, $example]);
    $xlsx->downloadAs('plantilla_importacion_viajes.xlsx');
    exit;
}
?>

<div class="max-w-4xl mx-auto py-10 px-4">
    <div class="mb-8">
        <a href="trips.php" class="text-sm text-gray-500 hover:text-brand-600 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            Volver a Viajes
        </a>
        <h1 class="text-3xl font-black text-slate-950">Importar Viajes desde Excel (.xlsx)</h1>
        <p class="text-slate-500 mt-2 font-medium">Carga masiva de operaciones mediante archivo Excel.</p>
    </div>

    <?php if ($message): ?>
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 mb-6 rounded-r-xl shadow-sm">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-emerald-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-emerald-800 font-bold"><?php echo $message; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-rose-50 border-l-4 border-rose-500 p-4 mb-6 rounded-r-xl shadow-sm">
            <div class="flex items-center mb-2">
                <svg class="h-5 w-5 text-rose-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
                <p class="text-rose-800 font-black"><?php echo $error; ?></p>
            </div>
            <ul class="list-disc list-inside text-xs text-rose-700 space-y-1 ml-7">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo $e; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
        <div class="bg-white p-8 rounded-3xl border-2 border-slate-100 shadow-xl">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-8">
                    <label class="block text-sm font-black text-slate-700 uppercase tracking-widest mb-4">Seleccionar
                        Archivo Excel</label>
                    <div class="relative group">
                        <input type="file" name="xlsx_file" accept=".xlsx" required
                            class="block w-full text-sm text-slate-500
                                      file:mr-4 file:py-3 file:px-6
                                      file:rounded-xl file:border-0
                                      file:text-sm file:font-black
                                      file:bg-brand-50 file:text-brand-700
                                      hover:file:bg-brand-100
                                      transition-all border-2 border-dashed border-slate-200 p-4 rounded-2xl group-hover:border-brand-300">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 italic font-medium">* Solo archivos .xlsx de Microsoft
                        Excel.</p>
                </div>

                <button type="submit"
                    class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-brand-600 hover:scale-[1.02] transition-all shadow-xl shadow-slate-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Procesar Importación
                </button>
            </form>
        </div>

        <div class="bg-brand-50 p-8 rounded-3xl border-2 border-brand-100">
            <h2 class="text-xl font-black text-brand-900 mb-4">Instrucciones</h2>
            <div class="space-y-4 text-sm text-brand-800 font-medium leading-relaxed">
                <p>1. Descarga la plantilla oficial en formato <strong class="text-brand-950 font-black">.xlsx</strong>.
                </p>
                <p>2. Asegúrate de que las <strong class="text-brand-950 font-black">Placas</strong> y <strong
                        class="text-brand-950 font-black">Documentos de Conductor</strong> ya existan en el sistema.</p>
                <p>3. El sistema verificará automáticamente las retenciones y comisiones basadas en la configuración
                    actual.</p>
                <p>4. Evita usar fórmulas o formatos especiales; usa solo texto y números.</p>
            </div>

            <a href="?download_template=1"
                class="mt-8 inline-flex items-center text-brand-700 font-black hover:bg-brand-200 border-2 border-brand-200 bg-white px-6 py-3 rounded-xl transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Descargar Plantilla Excel
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>