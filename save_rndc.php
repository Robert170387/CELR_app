<?php
/**
 * Handler: guardar / actualizar manifiesto RNDC
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rndc.php');
    exit;
}

validateCsrfToken();

$s  = fn($k) => trim($_POST[$k] ?? '') ?: null;
$f  = fn($k) => (float)($_POST[$k] ?? 0);
$i  = fn($k) => ($_POST[$k] ?? '') !== '' ? (int)$_POST[$k] : null;

$rndcId = (int)($_POST['rndc_id'] ?? 0);

// Validaciones básicas
$nro  = trim($_POST['nro_manifiesto'] ?? '');
$fecha = trim($_POST['fecha_expedicion'] ?? '');
if (!$nro || !$fecha) {
    header('Location: rndc_form.php?error=campos_requeridos' . ($rndcId ? "&id=$rndcId" : ''));
    exit;
}

// --- File Upload Handling ---
$manifest_file_path = null;
if (isset($_FILES['manifest_file']) && $_FILES['manifest_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/manifests/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_ext = pathinfo($_FILES['manifest_file']['name'], PATHINFO_EXTENSION);
    $file_name = 'rndc_' . time() . '_' . uniqid() . '.' . $file_ext;
    $manifest_file_path = $upload_dir . $file_name;
    move_uploaded_file($_FILES['manifest_file']['tmp_name'], $manifest_file_path);
} elseif ($rndcId > 0) {
    // Keep existing file if no new upload
    $stmtOld = $pdo->prepare("SELECT manifest_file FROM manifiestos_rndc WHERE id = ?");
    $stmtOld->execute([$rndcId]);
    $manifest_file_path = $stmtOld->fetchColumn() ?: null;
}

$fields = [
    $s('nro_manifiesto'),
    $s('autorizacion_rndc'),
    $s('nro_remesa'),
    $fecha,
    $s('fecha_vencimiento'),
    (int)($_POST['vehicle_id'] ?? 0),
    (int)($_POST['driver_id'] ?? 0),
    $i('trip_id'),
    $s('empresa_transporte'),
    $s('nit_empresa'),
    $s('remitente_nombre'),
    $s('remitente_nit'),
    $s('remitente_codigo'),
    $s('remitente_direccion'),
    $s('remitente_ciudad'),
    $s('destinatario_nombre'),
    $s('destinatario_nit'),
    $s('destinatario_codigo'),
    $s('destinatario_direccion'),
    $s('destinatario_ciudad'),
    $s('origen'),
    $s('destino'),
    $s('descripcion_mercancia'),
    ($_POST['peso_kg'] ?? '') !== '' ? (float)$_POST['peso_kg'] : null,
    ($_POST['unidades'] ?? '') !== '' ? (int)$_POST['unidades'] : null,
    $s('tipo_vehiculo'),
    $f('flete_pactado'),
    $f('anticipo'),
    $f('saldo'),
    $s('cargue_pagado_por'),
    $s('descargue_pagado_por'),
    $s('lugar_pago'),
    $s('fecha_pago_saldo'),
    $s('estado') ?? 'Activo',
    $s('notas'),
    $manifest_file_path,
];

if ($rndcId > 0) {
    $stmt = $pdo->prepare("UPDATE manifiestos_rndc SET
        nro_manifiesto=?, autorizacion_rndc=?, nro_remesa=?,
        fecha_expedicion=?, fecha_vencimiento=?,
        vehicle_id=?, driver_id=?, trip_id=?,
        empresa_transporte=?, nit_empresa=?,
        remitente_nombre=?, remitente_nit=?, remitente_codigo=?,
        remitente_direccion=?, remitente_ciudad=?,
        destinatario_nombre=?, destinatario_nit=?, destinatario_codigo=?,
        destinatario_direccion=?, destinatario_ciudad=?,
        origen=?, destino=?,
        descripcion_mercancia=?, peso_kg=?, unidades=?, tipo_vehiculo=?,
        flete_pactado=?, anticipo=?, saldo=?,
        cargue_pagado_por=?, descargue_pagado_por=?,
        lugar_pago=?, fecha_pago_saldo=?,
        estado=?, notas=?, manifest_file=?
        WHERE id=?");
    $stmt->execute(array_merge($fields, [$rndcId]));
} else {
    $stmt = $pdo->prepare("INSERT INTO manifiestos_rndc (
        nro_manifiesto, autorizacion_rndc, nro_remesa,
        fecha_expedicion, fecha_vencimiento,
        vehicle_id, driver_id, trip_id,
        empresa_transporte, nit_empresa,
        remitente_nombre, remitente_nit, remitente_codigo,
        remitente_direccion, remitente_ciudad,
        destinatario_nombre, destinatario_nit, destinatario_codigo,
        destinatario_direccion, destinatario_ciudad,
        origen, destino,
        descripcion_mercancia, peso_kg, unidades, tipo_vehiculo,
        flete_pactado, anticipo, saldo,
        cargue_pagado_por, descargue_pagado_por,
        lugar_pago, fecha_pago_saldo,
        estado, notas, manifest_file
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute($fields);
    $rndcId = (int)$pdo->lastInsertId();
}

// Sincronizar campos RNDC en la tabla trips si hay trip_id
$tripId = $i('trip_id');
if ($tripId) {
    try {
        $sync = $pdo->prepare("UPDATE trips SET
            manifest_number        = ?,
            autorizacion_rndc      = ?,
            nro_remesa             = ?,
            empresa_transporte     = ?,
            nit_empresa_transporte = ?,
            remitente_nombre       = ?,
            remitente_codigo       = ?,
            destinatario_nombre    = ?,
            destinatario_codigo    = ?,
            cargue_pagado_por      = ?,
            descargue_pagado_por   = ?,
            lugar_pago             = ?,
            fecha_pago_saldo       = ?
            WHERE id = ?");
        $sync->execute([
            $s('nro_manifiesto'),
            $s('autorizacion_rndc'),
            $s('nro_remesa'),
            $s('empresa_transporte'),
            $s('nit_empresa'),
            $s('remitente_nombre'),
            $s('remitente_codigo'),
            $s('destinatario_nombre'),
            $s('destinatario_codigo'),
            $s('cargue_pagado_por'),
            $s('descargue_pagado_por'),
            $s('lugar_pago'),
            $s('fecha_pago_saldo'),
            $tripId,
        ]);
    } catch (PDOException $e) {
        // Columnas RNDC en trips pueden no existir aún — ignorar
    }
}

header("Location: rndc.php?msg=saved");
exit;
