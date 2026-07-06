<?php
/**
 * Formulario Manifiesto RNDC — Crear / Editar
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/functions.php';

$csrf_token = getCsrfToken();
$editMode   = isset($_GET['id']);
$rndcId     = $editMode ? (int)$_GET['id'] : 0;

if ($editMode) {
    $stmt = $pdo->prepare("SELECT * FROM manifiestos_rndc WHERE id = ?");
    $stmt->execute([$rndcId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) die("Manifiesto #$rndcId no encontrado.");
} else {
    $r = [
        'id' => null, 'trip_id' => '', 'vehicle_id' => '', 'driver_id' => '',
        'nro_manifiesto' => '', 'autorizacion_rndc' => '', 'nro_remesa' => '',
        'fecha_expedicion' => date('Y-m-d'), 'fecha_vencimiento' => '',
        'empresa_transporte' => '', 'nit_empresa' => '',
        'remitente_nombre' => '', 'remitente_nit' => '', 'remitente_codigo' => '',
        'remitente_direccion' => '', 'remitente_ciudad' => '',
        'destinatario_nombre' => '', 'destinatario_nit' => '', 'destinatario_codigo' => '',
        'destinatario_direccion' => '', 'destinatario_ciudad' => '',
        'origen' => '', 'destino' => '',
        'descripcion_mercancia' => '', 'peso_kg' => '', 'unidades' => '', 'tipo_vehiculo' => '',
        'flete_pactado' => '', 'anticipo' => '', 'saldo' => '',
        'cargue_pagado_por' => '', 'descargue_pagado_por' => '',
        'lugar_pago' => '', 'fecha_pago_saldo' => '',
        'estado' => 'Activo', 'notas' => '', 'manifest_file' => '',
    ];
}

$vehicles = $pdo->query("SELECT id, placa FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll();
$drivers  = $pdo->query("SELECT id, CONCAT(firstname,' ',IFNULL(lastname,'')) AS nombre FROM personnel WHERE active=1 AND type='Conductor' ORDER BY firstname")->fetchAll();
$trips_list = $pdo->query("SELECT t.id, v.placa, t.date_load, t.origin, t.destination FROM trips t LEFT JOIN vehicles v ON v.id=t.vehicle_id ORDER BY t.date_load DESC LIMIT 200")->fetchAll();

$msg = $_GET['msg'] ?? '';
?>

<div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="rndc.php" class="text-gray-400 hover:text-gray-600">← Manifiestos</a>
        <span class="text-gray-300">/</span>
        <h2 class="text-xl font-bold text-gray-900">
            <?php echo $editMode ? 'Editar Manifiesto #'.$rndcId : 'Nuevo Manifiesto RNDC'; ?>
        </h2>
    </div>

    <?php if ($msg === 'saved'): ?>
        <div class="alert-card alert-card-success">
            <span class="alert-card-icon">✅</span>
            <div class="alert-card-body"><div class="alert-card-title">Éxito</div><div class="alert-card-text">Manifiesto guardado. <a href="rndc.php" class="underline ml-2">Ver todos</a></div></div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php endif; ?>

    <form action="save_rndc.php" method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="rndc_id"   value="<?php echo $rndcId; ?>">

        <!-- ① Identificación -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">① Identificación del Manifiesto</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Manifiesto *</label>
                    <input type="text" name="nro_manifiesto" required
                           value="<?php echo htmlspecialchars($r['nro_manifiesto']); ?>"
                           placeholder="MFC-2024-000123"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Autorización RNDC</label>
                    <input type="text" name="autorizacion_rndc"
                           value="<?php echo htmlspecialchars($r['autorizacion_rndc'] ?? ''); ?>"
                           placeholder="Código RNDC"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Remesa</label>
                    <input type="text" name="nro_remesa"
                           value="<?php echo htmlspecialchars($r['nro_remesa'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select name="estado" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <?php foreach (['Borrador','Activo','Finalizado','Anulado'] as $e): ?>
                            <option value="<?php echo $e; ?>" <?php echo $r['estado']===$e?'selected':''; ?>><?php echo $e; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Expedición *</label>
                    <input type="date" name="fecha_expedicion" required value="<?php echo $r['fecha_expedicion']; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                    <input type="date" name="fecha_vencimiento" value="<?php echo $r['fecha_vencimiento'] ?? ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ② Vehículo y Conductor -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">② Vehículo y Conductor</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehículo *</label>
                    <select name="vehicle_id" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $r['vehicle_id']==$v['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($v['placa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conductor *</label>
                    <select name="driver_id" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $r['driver_id']==$d['id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($d['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Viaje Asociado <span class="text-gray-400">(opcional)</span></label>
                    <select name="trip_id" id="trip_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                            onchange="autoFillTrip(this)">
                        <option value="">-- Sin viaje asociado --</option>
                        <?php foreach ($trips_list as $t): ?>
                            <option value="<?php echo $t['id']; ?>"
                                    data-origen="<?php echo htmlspecialchars($t['origin']); ?>"
                                    data-destino="<?php echo htmlspecialchars($t['destination']); ?>"
                                    <?php echo $r['trip_id']==$t['id']?'selected':''; ?>>
                                #<?php echo $t['id']; ?> — <?php echo htmlspecialchars($t['placa']); ?>
                                — <?php echo date('d/m/Y', strtotime($t['date_load'])); ?>
                                — <?php echo htmlspecialchars($t['origin']); ?> → <?php echo htmlspecialchars($t['destination']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- ③ Empresa de Transporte -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">③ Empresa de Transporte</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                    <input type="text" name="empresa_transporte" required
                           value="<?php echo htmlspecialchars($r['empresa_transporte']); ?>"
                           placeholder="Transportes del Oriente S.A.S."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIT</label>
                    <input type="text" name="nit_empresa"
                           value="<?php echo htmlspecialchars($r['nit_empresa'] ?? ''); ?>"
                           placeholder="900.123.456-7"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
            </div>
        </div>

        <!-- ④ Remitente -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">④ Remitente (Quien Envía)</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre / Razón Social *</label>
                    <input type="text" name="remitente_nombre" required
                           value="<?php echo htmlspecialchars($r['remitente_nombre']); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIT / Cédula</label>
                    <input type="text" name="remitente_nit"
                           value="<?php echo htmlspecialchars($r['remitente_nit'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código RNDC</label>
                    <input type="text" name="remitente_codigo"
                           value="<?php echo htmlspecialchars($r['remitente_codigo'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input type="text" name="remitente_ciudad"
                           value="<?php echo htmlspecialchars($r['remitente_ciudad'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <input type="text" name="remitente_direccion"
                           value="<?php echo htmlspecialchars($r['remitente_direccion'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ⑤ Destinatario -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">⑤ Destinatario (Quien Recibe)</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre / Razón Social *</label>
                    <input type="text" name="destinatario_nombre" required
                           value="<?php echo htmlspecialchars($r['destinatario_nombre']); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIT / Cédula</label>
                    <input type="text" name="destinatario_nit"
                           value="<?php echo htmlspecialchars($r['destinatario_nit'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código RNDC</label>
                    <input type="text" name="destinatario_codigo"
                           value="<?php echo htmlspecialchars($r['destinatario_codigo'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input type="text" name="destinatario_ciudad"
                           value="<?php echo htmlspecialchars($r['destinatario_ciudad'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <input type="text" name="destinatario_direccion"
                           value="<?php echo htmlspecialchars($r['destinatario_direccion'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ⑥ Ruta y Mercancía -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">⑥ Ruta y Mercancía</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Origen *</label>
                    <input type="text" name="origen" required id="campo_origen"
                           value="<?php echo htmlspecialchars($r['origen']); ?>"
                           placeholder="Medellín, Antioquia"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destino *</label>
                    <input type="text" name="destino" required id="campo_destino"
                           value="<?php echo htmlspecialchars($r['destino']); ?>"
                           placeholder="Bogotá, Cundinamarca"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción Mercancía</label>
                    <input type="text" name="descripcion_mercancia"
                           value="<?php echo htmlspecialchars($r['descripcion_mercancia'] ?? ''); ?>"
                           placeholder="Arena de río, cemento, mercancía general..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                    <input type="number" step="0.01" name="peso_kg"
                           value="<?php echo $r['peso_kg'] ?? ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidades</label>
                    <input type="number" name="unidades"
                           value="<?php echo $r['unidades'] ?? ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Vehículo</label>
                    <input type="text" name="tipo_vehiculo"
                           value="<?php echo htmlspecialchars($r['tipo_vehiculo'] ?? ''); ?>"
                           placeholder="Tractocamión, Camión rígido, Minimula..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ⑦ Financiero -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">⑦ Información Financiera</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Flete Pactado</label>
                    <input type="number" step="0.01" name="flete_pactado" id="flete_pactado"
                           value="<?php echo $r['flete_pactado'] ?? '0'; ?>"
                           oninput="calcSaldo()"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Anticipo</label>
                    <input type="number" step="0.01" name="anticipo" id="anticipo"
                           value="<?php echo $r['anticipo'] ?? '0'; ?>"
                           oninput="calcSaldo()"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Saldo <span class="text-gray-400 font-normal">(auto)</span></label>
                    <input type="number" step="0.01" name="saldo" id="saldo"
                           value="<?php echo $r['saldo'] ?? '0'; ?>"
                           readonly
                           class="w-full border border-gray-200 bg-gray-50 rounded px-3 py-2 text-sm font-semibold text-blue-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cargue Pagado Por</label>
                    <select name="cargue_pagado_por" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">--</option>
                        <?php foreach (['Remitente','Destinatario','Propietario'] as $op): ?>
                            <option value="<?php echo $op; ?>" <?php echo ($r['cargue_pagado_por']??'')===$op?'selected':''; ?>><?php echo $op; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descargue Pagado Por</label>
                    <select name="descargue_pagado_por" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">--</option>
                        <?php foreach (['Remitente','Destinatario','Propietario'] as $op): ?>
                            <option value="<?php echo $op; ?>" <?php echo ($r['descargue_pagado_por']??'')===$op?'selected':''; ?>><?php echo $op; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de Pago</label>
                    <input type="text" name="lugar_pago"
                           value="<?php echo htmlspecialchars($r['lugar_pago'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Pago Saldo</label>
                    <input type="date" name="fecha_pago_saldo" value="<?php echo $r['fecha_pago_saldo'] ?? ''; ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- ⑧ Notas -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">⑧ Notas</h3>
            <textarea name="notas" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                      placeholder="Observaciones, instrucciones especiales..."><?php echo htmlspecialchars($r['notas'] ?? ''); ?></textarea>
        </div>

        <!-- ⑨ Archivo -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wide mb-4 border-b pb-2">⑨ Archivo del Manifiesto</h3>
            <?php if (!empty($r['manifest_file'])): ?>
                <p class="mb-2">
                    <a href="<?php echo htmlspecialchars($r['manifest_file']); ?>" target="_blank" class="text-blue-600 hover:underline text-sm">
                        📄 Ver archivo actual
                    </a>
                </p>
            <?php endif; ?>
            <input type="file" name="manifest_file" accept=".pdf,.jpg,.jpeg,.png"
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <input type="hidden" name="existing_manifest_file" value="<?php echo htmlspecialchars($r['manifest_file'] ?? ''); ?>">
            <p class="mt-1 text-xs text-gray-400">Formatos permitidos: PDF, JPG, PNG.</p>
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-3">
            <a href="rndc.php" class="px-5 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded text-sm font-semibold hover:bg-blue-700">
                💾 Guardar Manifiesto
            </button>
        </div>
    </form>
</div>

<script>
function calcSaldo() {
    const flete   = parseFloat(document.getElementById('flete_pactado').value) || 0;
    const anticipo = parseFloat(document.getElementById('anticipo').value) || 0;
    document.getElementById('saldo').value = (flete - anticipo).toFixed(2);
}

function autoFillTrip(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (opt.value) {
        const o = opt.dataset.origen  || '';
        const d = opt.dataset.destino || '';
        if (o) document.getElementById('campo_origen').value  = o;
        if (d) document.getElementById('campo_destino').value = d;
    }
}

calcSaldo();

// ── Autoguardado (Borrador) ────────────────────────────────────
(function() {
    const STORAGE_KEY = 'rndc_draft_' + (<?php echo $rndcId ?: 0; ?>);
    const form = document.querySelector('form');
    if (!form) return;

    <?php if (!$editMode): ?>
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
        try {
            const data = JSON.parse(saved);
            const banner = document.createElement('div');
            banner.id = 'draft-banner';
            banner.style.cssText = 'background:#fef3c7;border:1px solid #f59e0b;color:#92400e;padding:12px 16px;border-radius:8px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;font-size:14px;';
            banner.innerHTML = '<span>\u{1F4DD} Borrador encontrado del ' + new Date(data._savedAt).toLocaleString() + '</span>' +
                '<span>' +
                '<button id="restore-draft" style="background:#f59e0b;color:#fff;border:none;padding:6px 14px;border-radius:4px;cursor:pointer;font-weight:600;margin-right:6px;">Restaurar</button>' +
                '<button id="discard-draft" style="background:transparent;color:#92400e;border:1px solid #f59e0b;padding:6px 14px;border-radius:4px;cursor:pointer;">Descartar</button>' +
                '</span>';
            form.parentNode.insertBefore(banner, form);

            document.getElementById('restore-draft').addEventListener('click', function() {
                for (const key in data) {
                    if (key === '_savedAt') continue;
                    const el = form.querySelector('[name="' + key + '"]');
                    if (el) {
                        if (el.type === 'checkbox') {
                            el.checked = data[key] === true || data[key] === '1';
                        } else if (el.type === 'radio') {
                            if (el.value === data[key]) el.checked = true;
                        } else if (el.type !== 'file') {
                            el.value = data[key];
                        }
                    }
                }
                banner.remove();
                calcSaldo();
            });
            document.getElementById('discard-draft').addEventListener('click', function() {
                localStorage.removeItem(STORAGE_KEY);
                banner.remove();
            });
        } catch(e) {}
    }
    <?php endif; ?>

    function saveDraft() {
        const data = {};
        const els = form.querySelectorAll('[name]');
        for (const el of els) {
            if (el.type === 'submit' || el.type === 'file' || el.type === 'hidden') continue;
            if (el.name === 'csrf_token') continue;
            if (el.type === 'checkbox') {
                data[el.name] = el.checked;
            } else if (el.type === 'radio') {
                if (el.checked) data[el.name] = el.value;
            } else {
                data[el.name] = el.value;
            }
        }
        data._savedAt = new Date().toISOString();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));

        let indicator = document.getElementById('draft-indicator');
        if (!indicator) {
            indicator = document.createElement('span');
            indicator.id = 'draft-indicator';
            indicator.style.cssText = 'position:fixed;bottom:12px;right:12px;background:#374151;color:#fff;font-size:11px;padding:4px 10px;border-radius:4px;z-index:9999;opacity:0.7;transition:opacity 0.3s;';
            indicator.textContent = '\u{1F4BE} Borrador guardado';
            document.body.appendChild(indicator);
        }
        indicator.style.opacity = '1';
        clearTimeout(indicator._hideTimer);
        indicator._hideTimer = setTimeout(function() { indicator.style.opacity = '0'; }, 3000);
    }

    let hasChanges = false;
    form.addEventListener('input', function() { hasChanges = true; });
    form.addEventListener('change', function() { hasChanges = true; });
    setInterval(function() {
        if (hasChanges) { saveDraft(); hasChanges = false; }
    }, 5000);

    window.addEventListener('beforeunload', function() {
        saveDraft();
    });

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
    localStorage.removeItem(STORAGE_KEY);
    <?php endif; ?>
})();
</script>

<?php include 'includes/footer.php'; ?>
