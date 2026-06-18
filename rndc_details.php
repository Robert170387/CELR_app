<?php
/**
 * Detalle / Impresión — Manifiesto Electrónico de Carga RNDC
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: rndc.php'); exit; }

$stmt = $pdo->prepare("SELECT m.*,
    v.placa, v.brand, v.model, v.year,
    CONCAT(p.firstname,' ',IFNULL(p.lastname,'')) AS conductor,
    p.license_number
    FROM manifiestos_rndc m
    LEFT JOIN vehicles  v ON v.id = m.vehicle_id
    LEFT JOIN personnel p ON p.id = m.driver_id
    WHERE m.id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$m) die("Manifiesto #$id no encontrado.");

$generado = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Manifiesto <?php echo htmlspecialchars($m['nro_manifiesto']); ?> — CELR</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; background: #f0f4ff; }

.page-wrapper { max-width: 900px; margin: 20px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.12); }

/* Header oficial */
.doc-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 60%, #7c3aed 100%);
    color: #fff;
    padding: 20px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.doc-header .org h1 { font-size: 20px; font-weight: 900; letter-spacing: 2px; }
.doc-header .org p  { font-size: 10px; opacity: .8; margin-top: 2px; }
.doc-header .doc-id { text-align: right; }
.doc-header .doc-id .nro { font-size: 18px; font-weight: 800; }
.doc-header .doc-id .tipo { font-size: 11px; opacity: .85; }

.doc-body { padding: 24px 28px; }

/* Secciones */
.section { margin-bottom: 16px; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; }
.section-title {
    background: #1e3a8a;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    padding: 6px 12px;
}
.section-body { padding: 12px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px 16px; }
.field label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: .3px; display: block; margin-bottom: 2px; }
.field span  { font-size: 11px; color: #111; font-weight: 600; display: block; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; min-height: 18px; }

/* Badges */
.badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 700; }
.badge-green  { background: #d1fae5; color: #065f46; }
.badge-blue   { background: #dbeafe; color: #1e3a8a; }
.badge-gray   { background: #f3f4f6; color: #374151; }
.badge-red    { background: #fee2e2; color: #991b1b; }

/* Tabla mercancía */
table.merch { width: 100%; border-collapse: collapse; }
table.merch th { background: #e8edf5; font-size: 9px; text-transform: uppercase; padding: 6px 8px; text-align: left; border: 1px solid #d1d5db; }
table.merch td { padding: 7px 8px; border: 1px solid #e5e7eb; font-size: 11px; }

/* Financiero */
.fin-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f0f0f0; }
.fin-row:last-child { border-bottom: none; font-weight: 700; font-size: 13px; color: #1e3a8a; }
.fin-label { color: #6b7280; font-size: 10px; }

/* Firmas */
.firma-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 8px; }
.firma-box { border-top: 2px solid #374151; padding-top: 8px; text-align: center; }
.firma-box .firma-name { font-size: 11px; font-weight: 700; margin-bottom: 2px; }
.firma-box .firma-label { font-size: 9px; color: #6b7280; text-transform: uppercase; }

/* Footer */
.doc-footer {
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    padding: 10px 28px;
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    color: #94a3b8;
}

/* Barra de acciones */
.action-bar {
    background: linear-gradient(135deg, #0f172a, #1e3a8a);
    padding: 12px 28px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 16px; border-radius: 7px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; }
.btn-print { background: linear-gradient(135deg,#10b981,#059669); color:#fff; }
.btn-back  { background: rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); }
.btn-edit  { background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }

@media print {
    body { background: #fff; }
    .action-bar { display: none !important; }
    .page-wrapper { box-shadow: none; border-radius: 0; max-width: 100%; margin: 0; }
    .doc-header, .section-title { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    @page { margin: 10mm; size: A4 portrait; }
}
</style>
</head>
<body>
<div class="page-wrapper">

    <!-- Acciones -->
    <div class="action-bar">
        <a href="rndc.php" class="btn btn-back">← Volver</a>
        <?php if ($m['estado'] !== 'Anulado'): ?>
        <a href="rndc_form.php?id=<?php echo $m['id']; ?>" class="btn btn-edit">✏ Editar</a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-print">🖨 Imprimir / PDF</button>
    </div>

    <!-- Encabezado oficial -->
    <div class="doc-header">
        <div class="org">
            <h1>CELR</h1>
            <p>Sistema de Gestión de Flota — Robert Serrano</p>
            <p>Manifiesto Electrónico de Carga — Decreto 1079/2015</p>
        </div>
        <div class="doc-id">
            <div class="tipo">MANIFIESTO DE CARGA</div>
            <div class="nro"><?php echo htmlspecialchars($m['nro_manifiesto']); ?></div>
            <?php
            $badge_class = match($m['estado']) {
                'Activo'     => 'badge-green',
                'Finalizado' => 'badge-blue',
                'Anulado'    => 'badge-red',
                default      => 'badge-gray',
            };
            ?>
            <span class="badge <?php echo $badge_class; ?>"><?php echo $m['estado']; ?></span>
        </div>
    </div>

    <div class="doc-body">

        <!-- Identificación -->
        <div class="section">
            <div class="section-title">Identificación</div>
            <div class="section-body">
                <div class="grid-3">
                    <div class="field"><label>Autorización RNDC</label><span><?php echo htmlspecialchars($m['autorizacion_rndc'] ?? '—'); ?></span></div>
                    <div class="field"><label>Nro. Remesa</label><span><?php echo htmlspecialchars($m['nro_remesa'] ?? '—'); ?></span></div>
                    <div class="field"><label>Fecha Expedición</label><span><?php echo date('d/m/Y', strtotime($m['fecha_expedicion'])); ?></span></div>
                    <div class="field"><label>Fecha Vencimiento</label><span><?php echo $m['fecha_vencimiento'] ? date('d/m/Y', strtotime($m['fecha_vencimiento'])) : '—'; ?></span></div>
                    <div class="field"><label>Empresa Transportadora</label><span><?php echo htmlspecialchars($m['empresa_transporte']); ?></span></div>
                    <div class="field"><label>NIT Empresa</label><span><?php echo htmlspecialchars($m['nit_empresa'] ?? '—'); ?></span></div>
                </div>
            </div>
        </div>

        <!-- Vehículo y Conductor -->
        <div class="section">
            <div class="section-title">Vehículo y Conductor</div>
            <div class="section-body">
                <div class="grid-3">
                    <div class="field"><label>Placa</label><span><?php echo htmlspecialchars($m['placa'] ?? '—'); ?></span></div>
                    <div class="field"><label>Vehículo</label><span><?php echo htmlspecialchars(trim(($m['brand']??'').' '.($m['model']??'').' '.($m['year']??'')) ?: '—'); ?></span></div>
                    <div class="field"><label>Tipo</label><span><?php echo htmlspecialchars($m['tipo_vehiculo'] ?? '—'); ?></span></div>
                    <div class="field"><label>Conductor</label><span><?php echo htmlspecialchars($m['conductor'] ?? '—'); ?></span></div>
                    <div class="field"><label>Licencia</label><span><?php echo htmlspecialchars($m['license_number'] ?? '—'); ?></span></div>
                </div>
            </div>
        </div>

        <!-- Remitente y Destinatario -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
            <div class="section" style="margin-bottom:0">
                <div class="section-title">Remitente (Origen)</div>
                <div class="section-body">
                    <div class="field" style="margin-bottom:6px"><label>Nombre</label><span><?php echo htmlspecialchars($m['remitente_nombre']); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>NIT/Cédula</label><span><?php echo htmlspecialchars($m['remitente_nit'] ?? '—'); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>Código RNDC</label><span><?php echo htmlspecialchars($m['remitente_codigo'] ?? '—'); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>Ciudad</label><span><?php echo htmlspecialchars($m['remitente_ciudad'] ?? '—'); ?></span></div>
                    <div class="field"><label>Dirección</label><span><?php echo htmlspecialchars($m['remitente_direccion'] ?? '—'); ?></span></div>
                </div>
            </div>
            <div class="section" style="margin-bottom:0">
                <div class="section-title">Destinatario (Destino)</div>
                <div class="section-body">
                    <div class="field" style="margin-bottom:6px"><label>Nombre</label><span><?php echo htmlspecialchars($m['destinatario_nombre']); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>NIT/Cédula</label><span><?php echo htmlspecialchars($m['destinatario_nit'] ?? '—'); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>Código RNDC</label><span><?php echo htmlspecialchars($m['destinatario_codigo'] ?? '—'); ?></span></div>
                    <div class="field" style="margin-bottom:6px"><label>Ciudad</label><span><?php echo htmlspecialchars($m['destinatario_ciudad'] ?? '—'); ?></span></div>
                    <div class="field"><label>Dirección</label><span><?php echo htmlspecialchars($m['destinatario_direccion'] ?? '—'); ?></span></div>
                </div>
            </div>
        </div>

        <!-- Ruta y Mercancía -->
        <div class="section">
            <div class="section-title">Ruta y Mercancía</div>
            <div class="section-body">
                <div class="grid-2" style="margin-bottom:12px">
                    <div class="field"><label>Origen</label><span><?php echo htmlspecialchars($m['origen']); ?></span></div>
                    <div class="field"><label>Destino</label><span><?php echo htmlspecialchars($m['destino']); ?></span></div>
                </div>
                <table class="merch">
                    <thead>
                        <tr>
                            <th>Descripción Mercancía</th>
                            <th style="width:120px">Peso (kg)</th>
                            <th style="width:100px">Unidades</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($m['descripcion_mercancia'] ?? '—'); ?></td>
                            <td><?php echo $m['peso_kg'] ? number_format($m['peso_kg'],2,',','.') : '—'; ?></td>
                            <td><?php echo $m['unidades'] ?? '—'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Financiero -->
        <div class="section">
            <div class="section-title">Información Financiera</div>
            <div class="section-body">
                <div class="grid-2">
                    <div>
                        <div class="fin-row"><span class="fin-label">Flete Pactado</span><span>$<?php echo number_format($m['flete_pactado'],0,',','.'); ?></span></div>
                        <div class="fin-row"><span class="fin-label">Anticipo</span><span>$<?php echo number_format($m['anticipo'],0,',','.'); ?></span></div>
                        <div class="fin-row"><span class="fin-label">Saldo</span><span>$<?php echo number_format($m['saldo'],0,',','.'); ?></span></div>
                    </div>
                    <div>
                        <div class="field" style="margin-bottom:8px"><label>Cargue pagado por</label><span><?php echo htmlspecialchars($m['cargue_pagado_por'] ?? '—'); ?></span></div>
                        <div class="field" style="margin-bottom:8px"><label>Descargue pagado por</label><span><?php echo htmlspecialchars($m['descargue_pagado_por'] ?? '—'); ?></span></div>
                        <div class="field" style="margin-bottom:8px"><label>Lugar de pago</label><span><?php echo htmlspecialchars($m['lugar_pago'] ?? '—'); ?></span></div>
                        <div class="field"><label>Fecha pago saldo</label><span><?php echo $m['fecha_pago_saldo'] ? date('d/m/Y', strtotime($m['fecha_pago_saldo'])) : '—'; ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($m['notas']): ?>
        <!-- Notas -->
        <div class="section">
            <div class="section-title">Observaciones</div>
            <div class="section-body" style="font-style:italic;color:#555"><?php echo htmlspecialchars($m['notas']); ?></div>
        </div>
        <?php endif; ?>

        <!-- Firmas -->
        <div class="section">
            <div class="section-title">Firmas y Declaraciones</div>
            <div class="section-body">
                <p style="font-size:9px;color:#6b7280;margin-bottom:16px">
                    Declaro que la información contenida en este manifiesto es verídica y que la mercancía descrita
                    se entrega en las condiciones pactadas. Este documento se rige por el Decreto 1079 de 2015
                    y la Resolución 5380 de 2014 del Ministerio de Transporte de Colombia.
                </p>
                <div class="firma-grid">
                    <div class="firma-box">
                        <div style="height:40px"></div>
                        <div class="firma-name"><?php echo htmlspecialchars($m['conductor'] ?? '___________________________'); ?></div>
                        <div class="firma-label">Conductor — Licencia: <?php echo htmlspecialchars($m['license_number'] ?? '___________'); ?></div>
                    </div>
                    <div class="firma-box">
                        <div style="height:40px"></div>
                        <div class="firma-name"><?php echo htmlspecialchars($m['empresa_transporte']); ?></div>
                        <div class="firma-label">Empresa Transportadora — NIT: <?php echo htmlspecialchars($m['nit_empresa'] ?? '___________'); ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="doc-footer">
        <span>Manifiesto #<?php echo htmlspecialchars($m['nro_manifiesto']); ?></span>
        <span>Generado: <?php echo $generado; ?></span>
        <span>CELR — Sistema de Gestión de Flota</span>
    </div>
</div>
</body>
</html>
