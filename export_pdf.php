<?php
/**
 * Vista de impresión / PDF — todos los módulos
 * El navegador imprime con Ctrl+P o el botón de imprimir de la página
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

$modulo = $_GET['modulo'] ?? '';

$meses_nombres = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];

// ─── Cargar datos ─────────────────────────────────────────────────────────────
$titulo = 'Reporte';
$subtitulo = '';
$headers = [];
$rows = [];

switch ($modulo) {

    case 'viajes':
        $titulo = 'Registro de Viajes';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['status']))     { $where .= " AND t.status = ?";     $params[] = $_GET['status']; }
        if (!empty($_GET['vehicle_id'])) { $where .= " AND t.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
        if (!empty($_GET['fecha_desde'])){ $where .= " AND t.date_load >= ?"; $params[] = $_GET['fecha_desde']; }
        if (!empty($_GET['fecha_hasta'])){ $where .= " AND t.date_load <= ?"; $params[] = $_GET['fecha_hasta']; }

        $sql = "SELECT t.id, t.date_load, t.trip_type, v.placa,
                       CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
                       t.origin, t.destination, t.manifest_number,
                       t.flete_bruto, t.flete_neto, t.commission_value, t.status
                FROM trips t
                LEFT JOIN vehicles  v ON v.id = t.vehicle_id
                LEFT JOIN personnel p ON p.id = t.driver_id
                $where ORDER BY t.date_load DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['ID','Fecha','Tipo','Placa','Conductor','Origen','Destino','Manif.','Flete Bruto','Flete Neto','Comisión','Estado'];
        foreach ($data as $r) {
            $rows[] = [
                $r['id'], date('d/m/Y', strtotime($r['date_load'])),
                $r['trip_type'], $r['placa'], $r['conductor'],
                $r['origin'], $r['destination'], $r['manifest_number'],
                '$'.number_format($r['flete_bruto'],0,',','.'),
                '$'.number_format($r['flete_neto'],0,',','.'),
                '$'.number_format($r['commission_value'],0,',','.'),
                $r['status'],
            ];
        }
        if (!empty($_GET['fecha_desde']) || !empty($_GET['fecha_hasta'])) {
            $subtitulo = 'Del ' . ($_GET['fecha_desde'] ?? '...') . ' al ' . ($_GET['fecha_hasta'] ?? '...');
        }
        break;

    case 'gastos':
        $titulo = 'Registro de Gastos';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['vehicle_id'])) { $where .= " AND e.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
        if (!empty($_GET['category']))   { $where .= " AND e.category = ?";   $params[] = $_GET['category']; }
        if (!empty($_GET['fecha_desde'])){ $where .= " AND e.expense_date >= ?"; $params[] = $_GET['fecha_desde']; }
        if (!empty($_GET['fecha_hasta'])) { $where .= " AND e.expense_date <= ?"; $params[] = $_GET['fecha_hasta']; }

        $sql = "SELECT e.expense_date, v.placa, e.category, e.description,
                       e.amount, e.payment_method, e.supplier_name
                FROM expenses e
                LEFT JOIN vehicles v ON v.id = e.vehicle_id
                $where ORDER BY e.expense_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Fecha','Placa','Categoría','Descripción','Valor','Método Pago','Proveedor'];
        $total = 0;
        foreach ($data as $r) {
            $total += $r['amount'];
            $rows[] = [
                date('d/m/Y', strtotime($r['expense_date'])),
                $r['placa'], $r['category'], $r['description'],
                '$'.number_format($r['amount'],0,',','.'),
                $r['payment_method'], $r['supplier_name'],
            ];
        }
        $rows[] = ['','','','<strong>TOTAL</strong>','<strong>$'.number_format($total,0,',','.').'</strong>','',''];
        break;

    case 'compensado':
        $titulo = 'Compensado RC — Liquidaciones Conductor';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['mes']))    { $where .= " AND cr.mes = ?";    $params[] = (int)$_GET['mes']; }
        if (!empty($_GET['anio']))   { $where .= " AND cr.anio = ?";   $params[] = (int)$_GET['anio']; }
        if (!empty($_GET['estado'])) { $where .= " AND cr.estado = ?"; $params[] = $_GET['estado']; }
        if (!empty($_GET['anio']))   { $subtitulo = 'Año ' . $_GET['anio']; }

        $sql = "SELECT cr.codigo, cr.anio, cr.mes,
                       CONCAT(p.firstname,' ',p.lastname) AS conductor, v.placa,
                       cr.flete_neto_periodo, cr.neto_pagar, cr.estado, cr.fecha_pago
                FROM compensado_rc cr
                JOIN personnel p ON p.id = cr.personnel_id
                JOIN vehicles  v ON v.id = cr.vehicle_id
                $where ORDER BY cr.anio DESC, cr.mes DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Código','Año','Mes','Conductor','Placa','Flete Neto Período','Neto a Pagar','Estado','Fecha Pago'];
        foreach ($data as $r) {
            $rows[] = [
                $r['codigo'], $r['anio'],
                $meses_nombres[(int)$r['mes']] ?? $r['mes'],
                $r['conductor'], $r['placa'],
                '$'.number_format($r['flete_neto_periodo'],0,',','.'),
                '$'.number_format($r['neto_pagar'],0,',','.'),
                $r['estado'],
                $r['fecha_pago'] ? date('d/m/Y', strtotime($r['fecha_pago'])) : '—',
            ];
        }
        break;

    case 'flypass':
        $titulo = 'Flypass TAG — Movimientos';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['vehicle_id'])){ $where .= " AND fm.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
        if (!empty($_GET['fecha_desde'])){ $where .= " AND DATE(fm.fecha) >= ?"; $params[] = $_GET['fecha_desde']; }
        if (!empty($_GET['fecha_hasta'])){ $where .= " AND DATE(fm.fecha) <= ?"; $params[] = $_GET['fecha_hasta']; }

        $sql = "SELECT fm.fecha, v.placa AS vehicle_placa, fm.placa,
                       fm.tipo_movimiento, fm.valor, fm.peaje_nombre,
                       fm.descripcion, IF(fm.legalizado,'Sí','No') AS legalizado
                FROM flypass_movimientos fm
                LEFT JOIN vehicles v ON v.id = fm.vehicle_id
                $where ORDER BY fm.fecha DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Fecha','Placa Veh.','Placa TAG','Tipo','Valor','Peaje','Descripción','Legalizado'];
        $total = 0;
        foreach ($data as $r) {
            $total += $r['valor'];
            $rows[] = [
                date('d/m/Y H:i', strtotime($r['fecha'])),
                $r['vehicle_placa'], $r['placa'], $r['tipo_movimiento'],
                '$'.number_format($r['valor'],0,',','.'),
                $r['peaje_nombre'], $r['descripcion'], $r['legalizado'],
            ];
        }
        $rows[] = ['','','','<strong>TOTAL</strong>','<strong>$'.number_format($total,0,',','.').'</strong>','','',''];
        break;

    case 'tarjeta':
        $titulo = 'Tarjeta Débito — Movimientos';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['vehicle_id'])) { $where .= " AND tm.vehicle_id = ?"; $params[] = (int)$_GET['vehicle_id']; }
        if (!empty($_GET['tipo']))       { $where .= " AND tm.tipo = ?";        $params[] = $_GET['tipo']; }
        if (!empty($_GET['fecha_desde'])){ $where .= " AND tm.fecha >= ?";     $params[] = $_GET['fecha_desde']; }
        if (!empty($_GET['fecha_hasta'])){ $where .= " AND tm.fecha <= ?";     $params[] = $_GET['fecha_hasta']; }

        $sql = "SELECT tm.fecha, v.placa, tm.tipo,
                       tm.establecimiento, tm.descripcion, tm.valor, tm.cruzado
                FROM tarjeta_movimientos tm
                LEFT JOIN vehicles v ON v.id = tm.vehicle_id
                $where ORDER BY tm.fecha DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Fecha','Placa','Tipo','Establecimiento','Descripción','Valor','Estado'];
        $total = 0;
        foreach ($data as $r) {
            $total += abs($r['valor']);
            $rows[] = [
                date('d/m/Y', strtotime($r['fecha'])),
                $r['placa'], $r['tipo'], $r['establecimiento'], $r['descripcion'],
                '-$'.number_format(abs($r['valor']),0,',','.'),
                $r['cruzado'],
            ];
        }
        $rows[] = ['','','','','<strong>TOTAL</strong>','<strong>-$'.number_format($total,0,',','.').'</strong>',''];
        break;

    case 'talleres':
        $titulo = 'Directorio de Talleres';
        $params = [];
        $where  = "WHERE 1=1";
        if (!empty($_GET['activos']) && $_GET['activos']==='1') { $where .= " AND active=1"; }
        if (!empty($_GET['ciudad']))       { $where .= " AND ciudad=?";       $params[] = $_GET['ciudad']; }
        if (!empty($_GET['especialidad'])) { $where .= " AND especialidad=?"; $params[] = $_GET['especialidad']; }

        $sql = "SELECT nombre, especialidad, ciudad, departamento,
                       telefono, celular, email, contacto_principal, nit,
                       IF(active,'Activo','Inactivo') AS estado
                FROM talleres $where ORDER BY nombre";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Nombre','Especialidad','Ciudad','Dpto.','Teléfono','Celular','Email','Contacto','NIT','Estado'];
        foreach ($data as $r) {
            $rows[] = [
                $r['nombre'], $r['especialidad'], $r['ciudad'], $r['departamento'],
                $r['telefono'], $r['celular'], $r['email'],
                $r['contacto_principal'], $r['nit'], $r['estado'],
            ];
        }
        break;

    case 'flujo_caja':
        $anio = !empty($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
        $titulo = "Flujo de Caja — Año $anio";
        $sql = "SELECT mes, nombre_mes, total_fletes, total_gastos_viaje,
                       total_gastos_extras, total_anticipos, utilidad_bruta, num_viajes
                FROM view_flujo_caja_mensual WHERE anio = ? ORDER BY mes";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$anio]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $headers = ['Mes','Fletes Netos','Gastos Viaje','Gastos Extras','Anticipos','Utilidad Bruta','# Viajes'];
        $tf=0; $tg=0; $tge=0; $ta=0; $tu=0; $tv=0;
        foreach ($data as $r) {
            $tf  += $r['total_fletes'];
            $tg  += $r['total_gastos_viaje'];
            $tge += $r['total_gastos_extras'];
            $ta  += $r['total_anticipos'];
            $tu  += $r['utilidad_bruta'];
            $tv  += $r['num_viajes'];
            $rows[] = [
                $r['nombre_mes'],
                '$'.number_format($r['total_fletes'],0,',','.'),
                '$'.number_format($r['total_gastos_viaje'],0,',','.'),
                '$'.number_format($r['total_gastos_extras'],0,',','.'),
                '$'.number_format($r['total_anticipos'],0,',','.'),
                '$'.number_format($r['utilidad_bruta'],0,',','.'),
                $r['num_viajes'],
            ];
        }
        $rows[] = [
            '<strong>TOTAL</strong>',
            '<strong>$'.number_format($tf,0,',','.').'</strong>',
            '<strong>$'.number_format($tg,0,',','.').'</strong>',
            '<strong>$'.number_format($tge,0,',','.').'</strong>',
            '<strong>$'.number_format($ta,0,',','.').'</strong>',
            '<strong>$'.number_format($tu,0,',','.').'</strong>',
            "<strong>$tv</strong>",
        ];
        break;

    default:
        $titulo = 'Módulo no válido';
}

$generado = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($titulo); ?> — CELR</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a2e; background: #f0f4ff; }

  /* ── Pantalla ─────────────────────────── */
  .page-wrapper { max-width: 1100px; margin: 20px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.12); }

  .pdf-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #7c3aed 100%);
    color: #fff;
    padding: 24px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .pdf-header .logo-area h1 { font-size: 22px; font-weight: 800; letter-spacing: 1px; }
  .pdf-header .logo-area p  { font-size: 11px; opacity: .8; margin-top: 2px; }
  .pdf-header .doc-info { text-align: right; font-size: 10px; opacity: .9; }
  .pdf-header .doc-info .doc-title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }

  .pdf-body { padding: 24px 32px; }

  .meta-bar { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
  .meta-chip {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border: 1px solid #93c5fd;
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 11px;
    color: #1d4ed8;
    font-weight: 600;
  }

  table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
  thead tr { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; }
  thead th { padding: 9px 8px; text-align: left; font-weight: 700; white-space: nowrap; }
  tbody tr:nth-child(even) { background: #f0f7ff; }
  tbody tr:last-child { background: #dbeafe; font-weight: 700; }
  tbody td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
  tbody tr:hover { background: #dbeafe; }

  .pdf-footer {
    margin-top: 20px;
    padding: 12px 32px;
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    color: #64748b;
  }

  /* ── Barra acciones (solo pantalla) ──── */
  .action-bar {
    display: flex;
    gap: 10px;
    padding: 14px 32px;
    background: linear-gradient(135deg, #0f172a, #1e3a8a);
    align-items: center;
    justify-content: flex-end;
  }
  .btn-action {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 8px; font-size: 12px; font-weight: 700;
    cursor: pointer; border: none; text-decoration: none;
    transition: transform .15s, box-shadow .15s;
  }
  .btn-action:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.3); }
  .btn-print { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
  .btn-back  { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
  .btn-xl    { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
  .btn-csv   { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; }

  /* ── Impresión ────────────────────────── */
  @media print {
    body { background: #fff; }
    .action-bar { display: none !important; }
    .page-wrapper { box-shadow: none; border-radius: 0; max-width: 100%; margin: 0; }
    .pdf-header { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    thead tr { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    table { font-size: 9px; }
    thead th, tbody td { padding: 5px 6px; }
    @page { margin: 10mm; size: A4 landscape; }
  }
</style>
</head>
<body>

<div class="page-wrapper">

  <!-- Barra de acciones (solo pantalla) -->
  <div class="action-bar">
    <?php
    // Construir query string preservando filtros
    $qs = http_build_query(array_merge($_GET, ['modulo' => $modulo]));
    $qs_xl  = http_build_query(array_merge($_GET, ['modulo' => $modulo, 'formato' => 'xlsx']));
    $qs_csv = http_build_query(array_merge($_GET, ['modulo' => $modulo, 'formato' => 'csv']));
    $back = match($modulo) {
        'viajes'       => 'trips.php',
        'gastos'       => 'expenses.php',
        'compensado'   => 'compensado_rc.php',
        'flypass'      => 'flypass.php',
        'tarjeta'      => 'tarjeta.php',
        'talleres'     => 'talleres.php',
        'flujo_caja'   => 'flujo_caja.php',
        'liquidaciones'=> 'settlements.php',
        default        => 'index.php',
    };
    ?>
    <a href="<?php echo $back; ?>" class="btn-action btn-back">← Volver</a>
    <a href="export.php?<?php echo htmlspecialchars($qs_xl); ?>" class="btn-action btn-xl">⬇ Excel (.xlsx)</a>
    <a href="export.php?<?php echo htmlspecialchars($qs_csv); ?>" class="btn-action btn-csv">⬇ CSV</a>
    <button onclick="window.print()" class="btn-action btn-print">🖨 Imprimir / PDF</button>
  </div>

  <!-- Encabezado del documento -->
  <div class="pdf-header">
    <div class="logo-area">
      <h1>CELR</h1>
      <p>Sistema de Gestión de Flota — Robert Serrano</p>
    </div>
    <div class="doc-info">
      <div class="doc-title"><?php echo htmlspecialchars($titulo); ?></div>
      <?php if ($subtitulo): ?>
        <div><?php echo htmlspecialchars($subtitulo); ?></div>
      <?php endif; ?>
      <div>Generado: <?php echo $generado; ?></div>
      <div><?php echo count($rows); ?> registro(s)</div>
    </div>
  </div>

  <!-- Cuerpo -->
  <div class="pdf-body">
    <?php if (!empty($_GET['fecha_desde']) || !empty($_GET['fecha_hasta']) || !empty($_GET['vehicle_id'])): ?>
    <div class="meta-bar">
      <?php if (!empty($_GET['fecha_desde'])): ?><span class="meta-chip">Desde: <?php echo $_GET['fecha_desde']; ?></span><?php endif; ?>
      <?php if (!empty($_GET['fecha_hasta'])): ?><span class="meta-chip">Hasta: <?php echo $_GET['fecha_hasta']; ?></span><?php endif; ?>
      <?php if (!empty($_GET['status'])): ?><span class="meta-chip">Estado: <?php echo htmlspecialchars($_GET['status']); ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
      <p style="text-align:center;padding:40px;color:#94a3b8;">No hay datos para mostrar con los filtros aplicados.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <?php foreach ($headers as $h): ?>
            <th><?php echo htmlspecialchars($h); ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <?php foreach ($row as $cell): ?>
            <td><?php echo $cell; ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Pie de página -->
  <div class="pdf-footer">
    <span>CELR — Sistema de Gestión de Flota</span>
    <span>Documento generado el <?php echo $generado; ?></span>
    <span>Confidencial — Uso interno</span>
  </div>

</div>

</body>
</html>
