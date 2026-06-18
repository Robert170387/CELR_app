<?php
/**
 * Exportación centralizada — XLSX / CSV
 * Parámetros GET: modulo, formato, + filtros del módulo
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/SimpleXLSXGen.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

$modulo  = $_GET['modulo']  ?? '';
$formato = $_GET['formato'] ?? 'xlsx'; // xlsx | csv

// ─── Datos por módulo ────────────────────────────────────────────────────────

function buildData(PDO $pdo, string $modulo): array {
    switch ($modulo) {

        // ── VIAJES ──────────────────────────────────────────────────────────
        case 'viajes':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['status']))     { $where .= " AND t.status = ?";       $params[] = $_GET['status']; }
            if (!empty($_GET['vehicle_id'])) { $where .= " AND t.vehicle_id = ?";   $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['fecha_desde'])){ $where .= " AND t.date_load >= ?";   $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta'])){ $where .= " AND t.date_load <= ?";   $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT t.id, t.date_load, t.trip_type, v.placa,
                           CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                           CASE WHEN c.person_type='Jurídica' THEN c.business_name
                                ELSE CONCAT(c.firstname,' ',c.lastname1) END AS cliente,
                           m.name AS material,
                           t.origin, t.destination,
                           t.manifest_number, t.empresa_transporte,
                           t.flete_bruto, t.flete_liquidado, t.comision_manifiesto,
                           t.total_deductibles, t.flete_neto,
                           t.advance_manifest, t.advance_owner,
                           t.gastos_totales, t.commission_value,
                           t.status
                    FROM trips t
                    LEFT JOIN vehicles  v ON v.id = t.vehicle_id
                    LEFT JOIN personnel p ON p.id = t.driver_id
                    LEFT JOIN materials m ON m.id = t.material_id
                    LEFT JOIN clients   c ON c.id = t.client_id
                    $where ORDER BY t.date_load DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha Carga','Tipo','Placa','Conductor','Cliente','Material',
                        'Origen','Destino','Manifiesto','Empresa Transporte',
                        'Flete Bruto','Flete Liquidado','Comisión Manifiesto',
                        'Retenciones','Flete Neto','Anticipo Manif.','Anticipo Trans.',
                        'Gastos Totales','Comisión','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['date_load'], $r['trip_type'], $r['placa'],
                    $r['conductor'], $r['cliente'], $r['material'],
                    $r['origin'], $r['destination'],
                    $r['manifest_number'], $r['empresa_transporte'],
                    (float)$r['flete_bruto'], (float)$r['flete_liquidado'],
                    (float)$r['comision_manifiesto'], (float)$r['total_deductibles'],
                    (float)$r['flete_neto'], (float)$r['advance_manifest'],
                    (float)$r['advance_owner'], (float)$r['gastos_totales'],
                    (float)$r['commission_value'], $r['status'],
                ];
            }
            return ['data' => $data, 'nombre' => 'viajes'];

        // ── GASTOS ──────────────────────────────────────────────────────────
        case 'gastos':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id'])) { $where .= " AND e.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['category']))   { $where .= " AND e.category = ?";   $params[] = $_GET['category']; }
            if (!empty($_GET['fecha_desde'])){ $where .= " AND e.expense_date >= ?"; $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta'])) { $where .= " AND e.expense_date <= ?"; $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT e.id, e.expense_date, v.placa,
                           CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                           e.category, e.description,
                           e.amount, e.payment_method, e.supplier_name,
                           e.trip_id, e.notes
                    FROM expenses e
                    LEFT JOIN vehicles  v ON v.id = e.vehicle_id
                    LEFT JOIN personnel p ON p.id = e.driver_id
                    $where ORDER BY e.expense_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha','Placa','Conductor','Categoría','Descripción',
                        'Valor','Método Pago','Proveedor','Viaje ID','Notas'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['expense_date'], $r['placa'], $r['conductor'],
                    $r['category'], $r['description'], (float)$r['amount'],
                    $r['payment_method'], $r['supplier_name'], $r['trip_id'], $r['notes'],
                ];
            }
            return ['data' => $data, 'nombre' => 'gastos'];

        // ── COMPENSADO RC ────────────────────────────────────────────────────
        case 'compensado':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['mes']))    { $where .= " AND cr.mes = ?";    $params[] = (int)$_GET['mes']; }
            if (!empty($_GET['anio']))   { $where .= " AND cr.anio = ?";   $params[] = (int)$_GET['anio']; }
            if (!empty($_GET['estado'])) { $where .= " AND cr.estado = ?"; $params[] = $_GET['estado']; }

            $sql = "SELECT cr.codigo, cr.anio, cr.mes,
                           CONCAT(p.firstname,' ',p.lastname) AS conductor,
                           v.placa,
                           cr.flete_neto_periodo, cr.gastos_periodo,
                           cr.comision_porcentaje, cr.comision_bruta,
                           cr.descuentos, cr.anticipos_periodo,
                           cr.neto_pagar, cr.estado,
                           cr.fecha_pago, cr.notas
                    FROM compensado_rc cr
                    JOIN personnel p ON p.id = cr.personnel_id
                    JOIN vehicles  v ON v.id = cr.vehicle_id
                    $where ORDER BY cr.anio DESC, cr.mes DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Código','Año','Mes','Conductor','Placa',
                        'Flete Neto Período','Gastos Período','% Comisión',
                        'Comisión Bruta','Descuentos','Anticipos',
                        'Neto a Pagar','Estado','Fecha Pago','Notas'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['codigo'], $r['anio'], $r['mes'], $r['conductor'], $r['placa'],
                    (float)$r['flete_neto_periodo'], (float)$r['gastos_periodo'],
                    (float)$r['comision_porcentaje'], (float)$r['comision_bruta'],
                    (float)$r['descuentos'], (float)$r['anticipos_periodo'],
                    (float)$r['neto_pagar'], $r['estado'], $r['fecha_pago'], $r['notas'],
                ];
            }
            return ['data' => $data, 'nombre' => 'compensado_rc'];

        // ── FLYPASS ──────────────────────────────────────────────────────────
        case 'flypass':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id']))    { $where .= " AND fm.vehicle_id = ?";    $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['legalizado']))    { $where .= " AND fm.legalizado = ?";    $params[] = (int)$_GET['legalizado']; }
            if (!empty($_GET['tipo_movimiento'])){ $where .= " AND fm.tipo_movimiento = ?"; $params[] = $_GET['tipo_movimiento']; }
            if (!empty($_GET['fecha_desde']))   { $where .= " AND DATE(fm.fecha) >= ?";  $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta']))   { $where .= " AND DATE(fm.fecha) <= ?";  $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT fm.id, fm.fecha, v.placa AS vehicle_placa, fm.placa,
                           fm.tipo_movimiento, fm.valor, fm.peaje_nombre,
                           fm.referencia_1, fm.referencia_2, fm.descripcion,
                           fm.legalizado, fm.fecha_legalizacion
                    FROM flypass_movimientos fm
                    LEFT JOIN vehicles v ON v.id = fm.vehicle_id
                    $where ORDER BY fm.fecha DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha','Placa Vehículo','Placa TAG','Tipo','Valor',
                        'Peaje','Ref. 1','Ref. 2','Descripción','Legalizado','Fecha Legalización'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['fecha'], $r['vehicle_placa'], $r['placa'],
                    $r['tipo_movimiento'], (float)$r['valor'], $r['peaje_nombre'],
                    $r['referencia_1'], $r['referencia_2'], $r['descripcion'],
                    $r['legalizado'] ? 'Sí' : 'No', $r['fecha_legalizacion'],
                ];
            }
            return ['data' => $data, 'nombre' => 'flypass'];

        // ── TARJETA DÉBITO ───────────────────────────────────────────────────
        case 'tarjeta':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id'])) { $where .= " AND tm.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['cruzado']))    { $where .= " AND tm.cruzado = ?";    $params[] = $_GET['cruzado']; }
            if (!empty($_GET['tipo']))       { $where .= " AND tm.tipo = ?";       $params[] = $_GET['tipo']; }
            if (!empty($_GET['fecha_desde'])) { $where .= " AND tm.fecha >= ?";    $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta'])) { $where .= " AND tm.fecha <= ?";    $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT tm.id, tm.fecha, v.placa, tm.tipo,
                           tm.establecimiento, tm.descripcion, tm.referencia,
                           tm.valor, tm.cruzado, tm.fecha_cruce, tm.detalle_cruce
                    FROM tarjeta_movimientos tm
                    LEFT JOIN vehicles v ON v.id = tm.vehicle_id
                    $where ORDER BY tm.fecha DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha','Placa','Tipo','Establecimiento',
                        'Descripción','Referencia','Valor','Cruzado','Fecha Cruce','Detalle'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['fecha'], $r['placa'], $r['tipo'],
                    $r['establecimiento'], $r['descripcion'], $r['referencia'],
                    (float)$r['valor'], $r['cruzado'], $r['fecha_cruce'], $r['detalle_cruce'],
                ];
            }
            return ['data' => $data, 'nombre' => 'tarjeta_debito'];

        // ── TALLERES ─────────────────────────────────────────────────────────
        case 'talleres':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['activos']) && $_GET['activos'] === '1') {
                $where .= " AND active = 1";
            }
            if (!empty($_GET['ciudad']))       { $where .= " AND ciudad = ?";      $params[] = $_GET['ciudad']; }
            if (!empty($_GET['especialidad'])) { $where .= " AND especialidad = ?"; $params[] = $_GET['especialidad']; }

            $sql = "SELECT nombre, especialidad, ciudad, departamento, direccion,
                           telefono, celular, email, contacto_principal, nit, notas,
                           IF(active,'Activo','Inactivo') AS estado
                    FROM talleres $where ORDER BY nombre";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Nombre','Especialidad','Ciudad','Departamento','Dirección',
                        'Teléfono','Celular','Email','Contacto','NIT','Notas','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = array_values($r);
            }
            return ['data' => $data, 'nombre' => 'talleres'];

        // ── FLUJO DE CAJA ─────────────────────────────────────────────────────
        case 'flujo_caja':
            $anio = !empty($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
            $sql = "SELECT mes, nombre_mes,
                           total_fletes, total_gastos_viaje, total_gastos_extras,
                           total_anticipos, utilidad_bruta, num_viajes
                    FROM view_flujo_caja_mensual
                    WHERE anio = ?
                    ORDER BY mes";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$anio]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Mes','Nombre','Fletes Netos','Gastos Viaje','Gastos Extras',
                        'Anticipos','Utilidad Bruta','# Viajes'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['mes'], $r['nombre_mes'],
                    (float)$r['total_fletes'], (float)$r['total_gastos_viaje'],
                    (float)$r['total_gastos_extras'], (float)$r['total_anticipos'],
                    (float)$r['utilidad_bruta'], (int)$r['num_viajes'],
                ];
            }
            return ['data' => $data, 'nombre' => "flujo_caja_$anio"];

        // ── LIQUIDACIONES ────────────────────────────────────────────────────
        case 'liquidaciones':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id'])) { $where .= " AND s.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['status']))     { $where .= " AND s.status = ?";     $params[] = $_GET['status']; }
            if (!empty($_GET['fecha_desde'])) { $where .= " AND s.date >= ?";     $params[] = $_GET['fecha_desde']; }

            $sql = "SELECT s.id, s.date, v.placa,
                           CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                           s.total_income, s.total_expenses,
                           s.net_amount, s.status, s.notes
                    FROM settlements s
                    LEFT JOIN vehicles  v ON v.id = s.vehicle_id
                    LEFT JOIN personnel p ON p.id = s.driver_id
                    $where ORDER BY s.date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['ID','Fecha','Placa','Conductor','Total Ingresos',
                        'Total Gastos','Neto','Estado','Notas'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['date'], $r['placa'], $r['conductor'],
                    (float)$r['total_income'], (float)$r['total_expenses'],
                    (float)$r['net_amount'], $r['status'], $r['notes'],
                ];
            }
            return ['data' => $data, 'nombre' => 'liquidaciones'];

        // ── RNDC ─────────────────────────────────────────────────────────────
        case 'rndc':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['vehicle_id'])) { $where .= " AND m.vehicle_id = ?";       $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['estado']))     { $where .= " AND m.estado = ?";            $params[] = $_GET['estado']; }
            if (!empty($_GET['fecha_desde'])){ $where .= " AND m.fecha_expedicion >= ?"; $params[] = $_GET['fecha_desde']; }
            if (!empty($_GET['fecha_hasta'])){ $where .= " AND m.fecha_expedicion <= ?"; $params[] = $_GET['fecha_hasta']; }

            $sql = "SELECT m.nro_manifiesto, m.autorizacion_rndc, m.nro_remesa,
                           m.fecha_expedicion, v.placa,
                           CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                           m.empresa_transporte, m.nit_empresa,
                           m.remitente_nombre, m.destinatario_nombre,
                           m.origen, m.destino,
                           m.descripcion_mercancia, m.peso_kg, m.unidades,
                           m.flete_pactado, m.anticipo, m.saldo,
                           m.cargue_pagado_por, m.descargue_pagado_por,
                           m.lugar_pago, m.fecha_pago_saldo, m.estado
                    FROM manifiestos_rndc m
                    LEFT JOIN vehicles  v ON v.id = m.vehicle_id
                    LEFT JOIN personnel p ON p.id = m.driver_id
                    $where ORDER BY m.fecha_expedicion DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Nro. Manifiesto','Autorización RNDC','Nro. Remesa','Fecha',
                        'Placa','Conductor','Empresa Trans.','NIT Empresa',
                        'Remitente','Destinatario','Origen','Destino',
                        'Mercancía','Peso(kg)','Unidades',
                        'Flete Pactado','Anticipo','Saldo',
                        'Cargue x','Descargue x','Lugar Pago','Fecha Pago Saldo','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['nro_manifiesto'], $r['autorizacion_rndc'], $r['nro_remesa'],
                    $r['fecha_expedicion'], $r['placa'], $r['conductor'],
                    $r['empresa_transporte'], $r['nit_empresa'],
                    $r['remitente_nombre'], $r['destinatario_nombre'],
                    $r['origen'], $r['destino'],
                    $r['descripcion_mercancia'], (float)$r['peso_kg'], (int)$r['unidades'],
                    (float)$r['flete_pactado'], (float)$r['anticipo'], (float)$r['saldo'],
                    $r['cargue_pagado_por'], $r['descargue_pagado_por'],
                    $r['lugar_pago'], $r['fecha_pago_saldo'], $r['estado'],
                ];
            }
            return ['data' => $data, 'nombre' => 'manifiestos_rndc'];

        // ── SOCIOS ───────────────────────────────────────────────────────────
        case 'socios':
            $params = [];
            $where  = "WHERE 1=1";
            if (!empty($_GET['activos']) && $_GET['activos'] === '1') {
                $where .= " AND s.active = 1";
            }
            if (!empty($_GET['buscar'])) {
                $where .= " AND (s.nombre LIKE ? OR s.documento LIKE ?)";
                $params[] = '%' . $_GET['buscar'] . '%';
                $params[] = '%' . $_GET['buscar'] . '%';
            }
            if (!empty($_GET['tipo'])) { $where .= " AND s.tipo = ?"; $params[] = $_GET['tipo']; }

            $sql = "SELECT s.nombre, s.tipo,
                           CONCAT(s.tipo_documento,' ',IFNULL(s.documento,'')) AS documento,
                           s.ciudad, s.telefono, s.celular, s.email,
                           s.banco, s.cuenta_bancaria, s.porcentaje_utilidad,
                           IF(s.active,'Activo','Inactivo') AS estado
                    FROM socios s $where ORDER BY s.nombre";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Nombre','Tipo','Documento','Ciudad','Teléfono','Celular',
                        'Email','Banco','Cuenta Bancaria','% Utilidad','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = array_values($r);
            }
            return ['data' => $data, 'nombre' => 'socios'];

        // ── RETENCIONES TRIBUTARIAS ───────────────────────────────────────────
        case 'retenciones':
            $anio = !empty($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
            $params = [$anio];
            $where  = "WHERE YEAR(t.date_load) = ?";
            if (!empty($_GET['mes']))        { $where .= " AND MONTH(t.date_load) = ?"; $params[] = (int)$_GET['mes']; }
            if (!empty($_GET['vehicle_id'])) { $where .= " AND t.vehicle_id = ?";       $params[] = (int)$_GET['vehicle_id']; }
            if (!empty($_GET['client_id']))  { $where .= " AND t.client_id = ?";        $params[] = (int)$_GET['client_id']; }

            $sql = "SELECT
                        t.date_load,
                        v.placa,
                        CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                        CASE WHEN c.person_type='Jurídica' THEN c.business_name
                             ELSE CONCAT(c.firstname,' ',c.lastname1) END AS cliente,
                        t.manifest_number,
                        t.flete_bruto,
                        t.total_deductibles,
                        t.flete_neto,
                        t.status
                    FROM trips t
                    LEFT JOIN vehicles  v ON v.id = t.vehicle_id
                    LEFT JOIN personnel p ON p.id = t.driver_id
                    LEFT JOIN clients   c ON c.id = t.client_id
                    $where
                    ORDER BY t.date_load DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $headers = ['Fecha','Placa','Conductor','Cliente','Manifiesto',
                        'Flete Bruto','Total Deductibles','Flete Neto','Estado'];
            $data = [$headers];
            foreach ($rows as $r) {
                $data[] = [
                    $r['date_load'], $r['placa'], $r['conductor'], $r['cliente'],
                    $r['manifest_number'],
                    (float)$r['flete_bruto'], (float)$r['total_deductibles'],
                    (float)$r['flete_neto'], $r['status'],
                ];
            }
            return ['data' => $data, 'nombre' => "retenciones_$anio"];

        default:
            return ['data' => [['Módulo no válido']], 'nombre' => 'export'];
    }
}

// ─── Construir y descargar ────────────────────────────────────────────────────

$result   = buildData($pdo, $modulo);
$data     = $result['data'];
$nombre   = $result['nombre'];
$filename = $nombre . '_' . date('Ymd_His');

if ($formato === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 para Excel
    foreach ($data as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}

// Default: XLSX
$xlsx = Shuchkin\SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs($filename . '.xlsx');
exit;
