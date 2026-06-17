<?php
/**
 * EDITOR DE VIAJES - VERSIÓN ULTRA BÁSICA
 * Sin Alpine.js, sin frameworks, solo PHP y HTML
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php'; // Genera token CSRF

// Generar token CSRF para el formulario
$csrf_token = getCsrfToken();

// Determinar modo: Crear o Editar
$editMode = isset($_GET['edit']) || isset($_GET['id']);
$tripId = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($editMode && !$tripId) {
    die("Error: No se especificó ID del viaje. Use: ?edit=1");
}

// Cargar viaje si estamos en modo edición
if ($editMode) {
    $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        die("Error: Viaje #$tripId no encontrado");
    }
} else {
    // Modo creación: inicializar array vacío con valores por defecto
    $trip = [
        'id' => null,
        'vehicle_id' => '',
        'driver_id' => '',
        'client_id' => '',
        'trip_type' => 'nacional',
        'status' => 'En Progreso',
        'origin' => '',
        'origin_state_id' => '',
        'origin_city_id' => '',
        'destination' => '',
        'destination_state_id' => '',
        'destination_city_id' => '',
        'date_load' => date('Y-m-d'),
        'date_unload' => '',
        'kms_start' => '0',
        'kms_end' => '',
        'manifest_number' => '',
        'flete_bruto' => '0',
        'weight_declared' => '',
        'weight_origin' => '',
        'weight_dest' => '',
        'percent_rete_fuente' => $config['default_rete_fuente'] ?? '1',
        'percent_rete_ica' => $config['default_rete_ica'] ?? '1',
        'percent_iva' => '0',  // No se usa IVA en los cálculos
        'percent_rete_iva' => '0',  // No se usa Rete IVA en los cálculos
        'advance_manifest' => '0',
        'advance_manifest_to_driver' => '0',
        'advance_owner' => '0',
        'advance_owner_responsible' => '',
        'settlement_status' => 'Pending',
        'settlement_notes' => '',
        'manifest_file' => '',
        'manifest_company_id' => '',
        'manifest_date' => '',
        'delivery_proof_url' => '',
        'commission_percent' => $config['ganancia_nacional_percent'] ?? '12',
        'percent_deductible_3' => '0',
        'value_deductible_4' => '0',
        'value_deductible_5' => '0',
        'value_deductible_6' => '0'
    ];
    $tripId = null;
}

// Cargar listas
if ($editMode) {
    $vehicles = $pdo->query("SELECT * FROM vehicles WHERE active=1 OR id = " . (int)$trip['vehicle_id'] . " ORDER BY placa")->fetchAll();
    $drivers = $pdo->query("SELECT * FROM personnel WHERE type='Conductor' AND (active=1 OR id = " . (int)$trip['driver_id'] . ") ORDER BY firstname")->fetchAll();
    $clients = $pdo->query("SELECT * FROM clients WHERE active=1 OR id = " . (int)$trip['client_id'] . " ORDER BY firstname, business_name")->fetchAll();
} else {
    $vehicles = $pdo->query("SELECT * FROM vehicles WHERE active=1 ORDER BY placa")->fetchAll();
    $drivers = $pdo->query("SELECT * FROM personnel WHERE type='Conductor' AND active=1 ORDER BY firstname")->fetchAll();
    $clients = $pdo->query("SELECT * FROM clients WHERE active=1 ORDER BY firstname, business_name")->fetchAll();
}
$materials = $pdo->query("SELECT * FROM materials WHERE active=1 ORDER BY name")->fetchAll();
$states = $pdo->query("SELECT * FROM loc_states ORDER BY name")->fetchAll();
$cities = $pdo->query("SELECT * FROM loc_cities ORDER BY name")->fetchAll();

// Filtrar ciudades por departamento
$originCities = [];
$destCities = [];
foreach ($cities as $city) {
    if ($city['state_id'] == $trip['origin_state_id']) {
        $originCities[] = $city;
    }
    if ($city['state_id'] == $trip['destination_state_id']) {
        $destCities[] = $city;
    }
}

// Config
$config = $pdo->query("SELECT * FROM config LIMIT 1")->fetch();

// Función helper para selected
function isSelected($value, $current) {
    return $value == $current ? 'selected' : '';
}

// Procesar mensajes
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Viaje #<?php echo $tripId; ?> - CELR</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f5f7fa; 
            margin: 0; 
            padding: 20px;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #2c3e50; 
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 { 
            color: #34495e; 
            margin-top: 30px;
            background: #ecf0f1;
            padding: 10px 15px;
            border-left: 4px solid #3498db;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            flex: 1;
        }
        label {
            display: block;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        select:focus {
            border-color: #3498db;
            outline: none;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
            margin-left: 10px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            text-align: right;
        }
        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 12px;
        }
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .location-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .location-box h3 {
            margin-top: 0;
            color: #495057;
            border-bottom: 2px solid #adb5bd;
            padding-bottom: 10px;
        }
        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
            .two-columns { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1><?php echo $editMode ? '✏️ Editar Viaje #' . $tripId : '➕ Crear Nuevo Viaje'; ?></h1>
    
    <?php if ($msg == 'success'): ?>
        <div class="alert alert-success" id="success-msg">
            ✅ Viaje #<?php echo $tripId; ?> actualizado correctamente.
            <a href="trip_details.php?id=<?php echo $tripId; ?>" style="margin-left: 15px; color: #155724; font-weight: bold; text-decoration: underline;">
                📄 Ver detalles del viaje
            </a>
        </div>
        <script>
            // Auto-scroll to top to show success message
            window.scrollTo({top: 0, behavior: 'smooth'});
            // Auto-hide after 5 seconds
            setTimeout(function() {
                var el = document.getElementById('success-msg');
                if (el) el.style.opacity = '0.5';
            }, 5000);
        </script>
    <?php endif; ?>
    
    <?php if ($editMode): ?>
    <!-- DEBUG: Mostrar datos cargados -->
    <div class="debug-info">
        <strong>Debug:</strong> Viaje cargado - ID: <?php echo $trip['id']; ?>, 
        Origen: <?php echo $trip['origin']; ?>, 
        Destino: <?php echo $trip['destination']; ?>,
        Fecha: <?php echo $trip['date_load']; ?>
    </div>
    <?php endif; ?>
    
    <form action="save_trip.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="trip_id" value="<?php echo $tripId; ?>">
        
        <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
        <h2>📋 Información Básica</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label>Estado</label>
                <select name="status">
                    <option value="En Progreso" <?php echo isSelected('En Progreso', $trip['status']); ?>>En Progreso</option>
                    <option value="Finalizado" <?php echo isSelected('Finalizado', $trip['status']); ?>>Finalizado</option>
                    <option value="Cancelado" <?php echo isSelected('Cancelado', $trip['status']); ?>>Cancelado</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tipo de Viaje</label>
                <select name="trip_type">
                    <option value="urbano" <?php echo isSelected('urbano', $trip['trip_type']); ?>>Urbano</option>
                    <option value="nacional" <?php echo isSelected('nacional', $trip['trip_type']); ?>>Nacional</option>
                    <option value="internacional" <?php echo isSelected('internacional', $trip['trip_type']); ?>>Internacional</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Material</label>
                <select name="material_id">
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($materials as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo isSelected($m['id'], $trip['material_id']); ?>>
                            <?php echo htmlspecialchars($m['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Vehículo *</label>
                <select name="vehicle_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>" <?php echo isSelected($v['id'], $trip['vehicle_id']); ?>>
                            <?php echo htmlspecialchars($v['placa'] . ' - ' . $v['brand']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Conductor *</label>
                <select name="driver_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($drivers as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo isSelected($d['id'], $trip['driver_id']); ?>>
                            <?php echo htmlspecialchars($d['firstname'] . ' ' . $d['lastname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Cliente *</label>
                <select name="client_id" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo isSelected($c['id'], $trip['client_id']); ?>>
                            <?php 
                            if ($c['person_type'] == 'Jurídica') {
                                echo htmlspecialchars($c['business_name']);
                            } else {
                                echo htmlspecialchars($c['firstname'] . ' ' . $c['lastname1']);
                            }
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- SECCIÓN 2: RUTA Y UBICACIONES -->
        <h2>🗺️ Ruta y Ubicaciones</h2>
        
        <div class="two-columns">
            <!-- ORIGEN -->
            <div class="location-box">
                <h3>📍 Origen</h3>
                
                <div class="form-group">
                    <label>Lugar de Origen *</label>
                    <input type="text" name="origin" required 
                           value="<?php echo htmlspecialchars($trip['origin']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Departamento</label>
                    <select name="origin_state_id" id="origin_state">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($states as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo isSelected($s['id'], $trip['origin_state_id']); ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Ciudad / Municipio</label>
                    <select name="origin_city_id" id="origin_city">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($originCities as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo isSelected($c['id'], $trip['origin_city_id']); ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- DESTINO -->
            <div class="location-box">
                <h3>🎯 Destino</h3>
                
                <div class="form-group">
                    <label>Lugar de Destino *</label>
                    <input type="text" name="destination" required 
                           value="<?php echo htmlspecialchars($trip['destination']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Departamento</label>
                    <select name="destination_state_id" id="dest_state">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($states as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo isSelected($s['id'], $trip['destination_state_id']); ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Ciudad / Municipio</label>
                    <select name="destination_city_id" id="dest_city">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($destCities as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo isSelected($c['id'], $trip['destination_city_id']); ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- FECHAS -->
        <div class="form-row" style="margin-top: 20px;">
            <div class="form-group">
                <label>Fecha de Carga *</label>
                <input type="date" name="date_load" required 
                       value="<?php echo $trip['date_load']; ?>">
            </div>
            
            <div class="form-group">
                <label>Fecha de Descarga</label>
                <input type="date" name="date_unload" 
                       value="<?php echo $trip['date_unload']; ?>">
            </div>
        </div>
        
        <!-- SECCIÓN 3: ODÓMETRO -->
        <h2>🚗 Odómetro</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label>Kilometraje Inicial *</label>
                <input type="number" step="0.1" name="kms_start" required 
                       value="<?php echo $trip['kms_start']; ?>">
            </div>
            
            <div class="form-group">
                <label>Kilometraje Final</label>
                <input type="number" step="0.1" name="kms_end" 
                       value="<?php echo $trip['kms_end']; ?>">
            </div>
        </div>
        
        <!-- SECCIÓN 4: MANIFIESTO -->
        <h2>📄 Manifiesto y Carga</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label>Número de Manifiesto *</label>
                <input type="text" name="manifest_number" required 
                       value="<?php echo htmlspecialchars($trip['manifest_number']); ?>">
            </div>
            
            <div class="form-group">
                <label>Valor Flete (Bruto) *</label>
                <input type="number" step="0.01" name="flete_bruto" required 
                       value="<?php echo $trip['flete_bruto']; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Peso Declarado (Ton) *</label>
                <input type="number" step="0.01" name="weight_declared" required 
                       value="<?php echo $trip['weight_declared']; ?>">
            </div>
            
            <div class="form-group">
                <label>Peso en Origen (Ton)</label>
                <input type="number" step="0.01" name="weight_origin" 
                       value="<?php echo $trip['weight_origin']; ?>">
            </div>
            
            <div class="form-group">
                <label>Peso en Destino (Ton)</label>
                <input type="number" step="0.01" name="weight_dest" 
                       value="<?php echo $trip['weight_dest']; ?>">
            </div>
        </div>
        
        <!-- Fecha del Manifiesto -->
        <div class="form-row">
            <div class="form-group">
                <label>Fecha del Manifiesto</label>
                <input type="date" name="manifest_date" 
                       value="<?php echo $trip['manifest_date'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label>URL Prueba de Entrega (POD)</label>
                <input type="text" name="delivery_proof_url" 
                       value="<?php echo htmlspecialchars($trip['delivery_proof_url'] ?? ''); ?>">
                <?php if ($trip['delivery_proof_url']): ?>
                    <p><a href="<?php echo $trip['delivery_proof_url']; ?>" target="_blank" style="color: #3498db; font-size: 12px;">🔗 Ver prueba de entrega</a></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Archivo del manifiesto -->
        <div class="form-group">
            <label>Archivo del Manifiesto (PDF/Imagen)</label>
            <?php if ($trip['manifest_file']): ?>
                <p>
                    <a href="<?php echo $trip['manifest_file']; ?>" target="_blank" style="color: #3498db;">
                        📄 Ver archivo actual
                    </a>
                </p>
            <?php endif; ?>
            <input type="file" name="manifest_file" accept=".pdf,.jpg,.jpeg,.png">
            <input type="hidden" name="existing_manifest_file" value="<?php echo $trip['manifest_file']; ?>">
        </div>
        
        <!-- Empresa de Manifiesto -->
        <div class="form-group" style="margin-top: 20px;">
            <label>Empresa de Manifiesto</label>
            <?php
            $companies = $pdo->query("SELECT * FROM manifest_companies WHERE active=1 ORDER BY name ASC")->fetchAll();
            ?>
            <select name="manifest_company_id">
                <option value="">-- Seleccione Empresa --</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo isSelected($c['id'], $trip['manifest_company_id'] ?? ''); ?>>
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- SECCIÓN 5: ANTICIPOS Y LIQUIDACIÓN -->
        <h2>💸 Anticipos y Liquidación</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label>Anticipo Manifiesto</label>
                <input type="number" step="0.01" name="advance_manifest" 
                       value="<?php echo $trip['advance_manifest'] ?? '0'; ?>">
                <label style="display: flex; align-items: center; margin-top: 8px; font-weight: normal;">
                    <input type="checkbox" name="advance_manifest_to_driver" value="1" 
                           <?php echo ($trip['advance_manifest_to_driver'] ?? 0) ? 'checked' : ''; ?> 
                           style="margin-right: 8px;">
                    Entregado al conductor
                </label>
            </div>
            
            <div class="form-group">
                <label>Anticipo Propietario</label>
                <input type="number" step="0.01" name="advance_owner" 
                       value="<?php echo $trip['advance_owner'] ?? '0'; ?>">
            </div>
            
            <div class="form-group">
                <label>Responsable del Anticipo</label>
                <input type="text" name="advance_owner_responsible" 
                       value="<?php echo htmlspecialchars($trip['advance_owner_responsible'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Estado de Liquidación</label>
                <select name="settlement_status">
                    <option value="Pending" <?php echo isSelected('Pending', $trip['settlement_status'] ?? 'Pending'); ?>>⏳ Pendiente</option>
                    <option value="Settled" <?php echo isSelected('Settled', $trip['settlement_status'] ?? ''); ?>>✅ Liquidado</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Notas de Liquidación</label>
                <textarea name="settlement_notes" rows="2"><?php echo htmlspecialchars($trip['settlement_notes'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <!-- SECCIÓN 6: COMISIÓN Y DEDUCIBLES ADICIONALES -->
        <h2>🎯 Comisión y Deducibles Adicionales</h2>
        
        <div class="form-row">
            <div class="form-group">
                <label>% Comisión del Conductor</label>
                <input type="number" step="0.01" name="commission_percent" 
                       value="<?php echo $trip['commission_percent'] ?? ($trip['trip_type'] == 'nacional' ? ($config['ganancia_nacional_percent'] ?? '12') : ($config['ganancia_urbano_percent'] ?? '8')); ?>">
            </div>
            
            <div class="form-group">
                <label>% Deducible 3</label>
                <input type="number" step="0.01" name="percent_deductible_3" 
                       value="<?php echo $trip['percent_deductible_3'] ?? '0'; ?>">
            </div>
            
            <div class="form-group">
                <label>Valor Deducible 4</label>
                <input type="number" step="0.01" name="value_deductible_4" 
                       value="<?php echo $trip['value_deductible_4'] ?? '0'; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Valor Deducible 5</label>
                <input type="number" step="0.01" name="value_deductible_5" 
                       value="<?php echo $trip['value_deductible_5'] ?? '0'; ?>">
            </div>
            
            <div class="form-group">
                <label>Valor Deducible 6</label>
                <input type="number" step="0.01" name="value_deductible_6" 
                       value="<?php echo $trip['value_deductible_6'] ?? '0'; ?>">
            </div>
        </div>
        
        <!-- SECCIÓN 7: RETENCIONES (Simplificada - sin IVA) -->
        <h2>📊 Retenciones</h2>
        
        <?php
        // Calcular valores para mostrar
        $rf_percent = $trip['percent_rete_fuente'] ?? $config['default_rete_fuente'] ?? 1;
        $ica_percent = $trip['percent_rete_ica'] ?? $config['default_rete_ica'] ?? 1;
        
        $flete = (float)($trip['flete_bruto'] ?? 0);
        $rf_value = round($flete * $rf_percent / 100);
        $ica_value = round($flete * $ica_percent / 100);
        ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>% Rete Fuente</label>
                <input type="number" step="0.01" name="percent_rete_fuente" 
                       value="<?php echo $rf_percent; ?>">
                <small style="color: #666;">Valor calculado: $<?php echo number_format($rf_value, 0, ',', '.'); ?></small>
            </div>
            
            <div class="form-group">
                <label>% Rete ICA</label>
                <input type="number" step="0.01" name="percent_rete_ica" 
                       value="<?php echo $ica_percent; ?>">
                <small style="color: #666;">Valor calculado: $<?php echo number_format($ica_value, 0, ',', '.'); ?></small>
            </div>
        </div>
        
        <!-- Campos ocultos para mantener compatibilidad con el backend -->
        <input type="hidden" name="percent_iva" value="0">
        <input type="hidden" name="percent_rete_iva" value="0">
        
        <!-- BOTONES -->
        <div class="actions">
            <a href="trip_details.php?id=<?php echo $tripId; ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
        </div>
        
    </form>
</div>

</body>
</html>
