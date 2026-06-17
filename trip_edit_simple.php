<?php
/**
 * CELR-App - Edición Simplificada de Viajes
 * Versión sin Alpine.js - 100% PHP/HTML
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Verificar ID de viaje
if (!isset($_GET['id'])) {
    header("Location: trips.php");
    exit;
}

$tripId = (int) $_GET['id'];

// Cargar datos del viaje
$stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
$stmt->execute([$tripId]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    die("Viaje no encontrado");
}

// Calcular balance
$tripBalance = calculateTripBalance($tripId);

// Cargar listas de opciones
$vehicles = $pdo->query("SELECT v.*, v.placa, v.brand FROM vehicles v WHERE v.active=1 OR v.id = {$trip['vehicle_id']} ORDER BY v.placa ASC")->fetchAll();
$drivers = $pdo->query("SELECT p.* FROM personnel p WHERE p.type='Conductor' AND (p.active=1 OR p.id = {$trip['driver_id']}) ORDER BY p.firstname ASC")->fetchAll();
$clients = $pdo->query("SELECT * FROM clients WHERE active=1 OR id = {$trip['client_id']} ORDER BY business_name, firstname ASC")->fetchAll();
$materials = $pdo->query("SELECT * FROM materials WHERE active=1 ORDER BY name ASC")->fetchAll();

// Cargar ubicaciones jerárquicas
$countries = $pdo->query("SELECT * FROM loc_countries ORDER BY name ASC")->fetchAll();
$states = $pdo->query("SELECT s.*, c.name as country_name FROM loc_states s JOIN loc_countries c ON s.country_id = c.id ORDER BY s.name ASC")->fetchAll();
$cities = $pdo->query("SELECT ci.*, s.name as state_name FROM loc_cities ci JOIN loc_states s ON ci.state_id = s.id ORDER BY ci.name ASC")->fetchAll();

// Obtener nombres de ubicaciones actuales
$originState = '';
$originCity = '';
$destState = '';
$destCity = '';

if ($trip['origin_state_id']) {
    $state = $pdo->query("SELECT name FROM loc_states WHERE id = {$trip['origin_state_id']}")->fetch();
    $originState = $state['name'] ?? '';
}
if ($trip['origin_city_id']) {
    $city = $pdo->query("SELECT name FROM loc_cities WHERE id = {$trip['origin_city_id']}")->fetch();
    $originCity = $city['name'] ?? '';
}
if ($trip['destination_state_id']) {
    $state = $pdo->query("SELECT name FROM loc_states WHERE id = {$trip['destination_state_id']}")->fetch();
    $destState = $state['name'] ?? '';
}
if ($trip['destination_city_id']) {
    $city = $pdo->query("SELECT name FROM loc_cities WHERE id = {$trip['destination_city_id']}")->fetch();
    $destCity = $city['name'] ?? '';
}

// Filtrar ciudades por departamento para selects dinámicos
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

// Procesar mensajes
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Viaje #<?php echo $tripId; ?> - CELR App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .form-section { background: white; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .form-section-header { border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px; }
        .form-section-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; }
        .form-section-subtitle { font-size: 0.875rem; color: #64748b; margin-top: 4px; }
        .form-label { display: block; font-weight: 600; font-size: 0.875rem; color: #374151; margin-bottom: 6px; }
        .form-label-required::after { content: " *"; color: #ef4444; }
        .form-input, .form-select { 
            width: 100%; 
            padding: 10px 14px; 
            border: 1px solid #d1d5db; 
            border-radius: 8px; 
            font-size: 0.95rem;
            background-color: white;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            ring: 2px solid #bfdbfe;
        }
        .form-grid { display: grid; gap: 20px; }
        .form-grid-2 { grid-template-columns: repeat(2, 1fr); }
        .form-grid-3 { grid-template-columns: repeat(3, 1fr); }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .financial-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
        }
        @media (max-width: 768px) {
            .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">

<div class="max-w-6xl mx-auto p-4 md:p-6">
    
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Editar Viaje #<?php echo $tripId; ?></h1>
        <p class="text-slate-500 mt-1">Modifica los detalles del viaje</p>
    </div>
    
    <!-- Mensajes -->
    <?php if ($success): ?>
        <div class="alert-success">✓ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-error">✗ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Formulario -->
    <form action="save_trip.php" method="POST" enctype="multipart/form-data" id="tripForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="trip_id" value="<?php echo $tripId; ?>">
        
        <!-- SECCIÓN 1: Información General -->
        <div class="form-section">
            <div class="form-section-header">
                <h2 class="form-section-title">Información General</h2>
                <p class="form-section-subtitle">Datos básicos del manifiesto, vehículo y conductor</p>
            </div>
            
            <div class="form-grid form-grid-3">
                <!-- Estado -->
                <div>
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="En Progreso" <?php echo $trip['status'] == 'En Progreso' ? 'selected' : ''; ?>>En Progreso</option>
                        <option value="Finalizado" <?php echo $trip['status'] == 'Finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                        <option value="Cancelado" <?php echo $trip['status'] == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <!-- Tipo de Viaje -->
                <div>
                    <label class="form-label">Tipo de Viaje</label>
                    <select name="trip_type" class="form-select">
                        <option value="urbano" <?php echo $trip['trip_type'] == 'urbano' ? 'selected' : ''; ?>>Urbano</option>
                        <option value="nacional" <?php echo $trip['trip_type'] == 'nacional' ? 'selected' : ''; ?>>Nacional</option>
                        <option value="internacional" <?php echo $trip['trip_type'] == 'internacional' ? 'selected' : ''; ?>>Internacional</option>
                    </select>
                </div>
                
                <!-- Material -->
                <div>
                    <label class="form-label">Material</label>
                    <select name="material_id" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($materials as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo $trip['material_id'] == $m['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Vehículo -->
                <div>
                    <label class="form-label form-label-required">Vehículo</label>
                    <select name="vehicle_id" required class="form-select">
                        <option value="">-- Seleccione Vehículo --</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $trip['vehicle_id'] == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['placa'] . ' - ' . $v['brand']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Conductor -->
                <div>
                    <label class="form-label form-label-required">Conductor</label>
                    <select name="driver_id" required class="form-select">
                        <option value="">-- Seleccione Conductor --</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $trip['driver_id'] == $d['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['firstname'] . ' ' . $d['lastname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Cliente -->
                <div>
                    <label class="form-label form-label-required">Cliente</label>
                    <select name="client_id" required class="form-select">
                        <option value="">-- Seleccione Cliente --</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $trip['client_id'] == $c['id'] ? 'selected' : ''; ?>>
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
        </div>
        
        <!-- SECCIÓN 2: Ruta y Ubicaciones -->
        <div class="form-section">
            <div class="form-section-header">
                <h2 class="form-section-title">Ruta y Ubicaciones</h2>
                <p class="form-section-subtitle">Origen, destino y fechas del viaje</p>
            </div>
            
            <div class="form-grid form-grid-2">
                <!-- ORIGEN -->
                <div>
                    <h3 style="font-weight: 700; color: #3b82f6; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid #bfdbfe;">📍 Origen</h3>
                    
                    <!-- Origen Texto -->
                    <div style="margin-bottom: 16px;">
                        <label class="form-label form-label-required">Lugar de Origen</label>
                        <input type="text" name="origin" required class="form-input" 
                               value="<?php echo htmlspecialchars($trip['origin']); ?>" 
                               placeholder="Ej: Piedecuesta">
                    </div>
                    
                    <!-- Departamento Origen -->
                    <div style="margin-bottom: 16px;">
                        <label class="form-label">Departamento</label>
                        <select name="origin_state_id" id="origin_state" class="form-select" 
                                onchange="loadCities('origin', this.value, <?php echo $trip['origin_city_id'] ?? 'null'; ?>)">
                            <option value="">-- Seleccione Departamento --</option>
                            <?php foreach ($states as $s): ?>
                                <option value="<?php echo $s['id']; ?>" 
                                        data-country="<?php echo $s['country_id']; ?>"
                                        <?php echo $trip['origin_state_id'] == $s['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']); ?> (<?php echo $s['country_name']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Ciudad Origen -->
                    <div>
                        <label class="form-label">Ciudad / Municipio</label>
                        <select name="origin_city_id" id="origin_city" class="form-select">
                            <option value="">-- Primero seleccione departamento --</option>
                            <?php foreach ($originCities as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $trip['origin_city_id'] == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- DESTINO -->
                <div>
                    <h3 style="font-weight: 700; color: #10b981; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid #a7f3d0;">🎯 Destino</h3>
                    
                    <!-- Destino Texto -->
                    <div style="margin-bottom: 16px;">
                        <label class="form-label form-label-required">Lugar de Destino</label>
                        <input type="text" name="destination" required class="form-input" 
                               value="<?php echo htmlspecialchars($trip['destination']); ?>" 
                               placeholder="Ej: Sabanalarga">
                    </div>
                    
                    <!-- Departamento Destino -->
                    <div style="margin-bottom: 16px;">
                        <label class="form-label">Departamento</label>
                        <select name="destination_state_id" id="dest_state" class="form-select" 
                                onchange="loadCities('dest', this.value, <?php echo $trip['destination_city_id'] ?? 'null'; ?>)">
                            <option value="">-- Seleccione Departamento --</option>
                            <?php foreach ($states as $s): ?>
                                <option value="<?php echo $s['id']; ?>" 
                                        data-country="<?php echo $s['country_id']; ?>"
                                        <?php echo $trip['destination_state_id'] == $s['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']); ?> (<?php echo $s['country_name']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Ciudad Destino -->
                    <div>
                        <label class="form-label">Ciudad / Municipio</label>
                        <select name="destination_city_id" id="dest_city" class="form-select">
                            <option value="">-- Primero seleccione departamento --</option>
                            <?php foreach ($destCities as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $trip['destination_city_id'] == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Fechas -->
            <div class="form-grid form-grid-2" style="margin-top: 24px;">
                <div>
                    <label class="form-label form-label-required">Fecha de Carga</label>
                    <input type="date" name="date_load" required class="form-input" 
                           value="<?php echo $trip['date_load']; ?>">
                </div>
                <div>
                    <label class="form-label">Fecha de Descarga</label>
                    <input type="date" name="date_unload" class="form-input" 
                           value="<?php echo $trip['date_unload']; ?>">
                </div>
            </div>
        </div>
        
        <!-- SECCIÓN 3: Odómetro -->
        <div class="form-section">
            <div class="form-section-header">
                <h2 class="form-section-title">Odómetro</h2>
                <p class="form-section-subtitle">Kilometraje del vehículo</p>
            </div>
            
            <div class="form-grid form-grid-3">
                <div>
                    <label class="form-label form-label-required">Km Inicial</label>
                    <input type="number" step="0.1" name="kms_start" required class="form-input" 
                           value="<?php echo $trip['kms_start']; ?>">
                </div>
                <div>
                    <label class="form-label">Km Final</label>
                    <input type="number" step="0.1" name="kms_end" class="form-input" 
                           value="<?php echo $trip['kms_end']; ?>">
                </div>
                <div>
                    <label class="form-label">Recorrido Total</label>
                    <input type="text" readonly class="form-input" 
                           value="<?php echo $trip['kms_end'] - $trip['kms_start']; ?> Km" 
                           style="background: #f1f5f9;">
                </div>
            </div>
        </div>
        
        <!-- SECCIÓN 4: Manifiesto y Carga -->
        <div class="form-section">
            <div class="form-section-header">
                <h2 class="form-section-title">Manifiesto y Carga</h2>
                <p class="form-section-subtitle">Datos del manifiesto y valores del flete</p>
            </div>
            
            <div class="form-grid form-grid-2">
                <div>
                    <label class="form-label form-label-required">Número de Manifiesto</label>
                    <input type="text" name="manifest_number" required class="form-input" 
                           value="<?php echo htmlspecialchars($trip['manifest_number']); ?>">
                </div>
                <div>
                    <label class="form-label form-label-required">Valor Flete (Bruto)</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><?php echo $config['currency_symbol'] ?? '$'; ?></span>
                        <input type="number" name="flete_bruto" required class="form-input" 
                               value="<?php echo $trip['flete_bruto']; ?>" 
                               style="padding-left: 28px;">
                    </div>
                </div>
            </div>
            
            <!-- Pesos -->
            <div class="form-grid form-grid-3" style="margin-top: 20px;">
                <div>
                    <label class="form-label form-label-required">Peso Declarado (Ton)</label>
                    <input type="number" step="0.01" name="weight_declared" required class="form-input" 
                           value="<?php echo $trip['weight_declared']; ?>">
                </div>
                <div>
                    <label class="form-label">Peso Origen (Ton)</label>
                    <input type="number" step="0.01" name="weight_origin" class="form-input" 
                           value="<?php echo $trip['weight_origin']; ?>">
                </div>
                <div>
                    <label class="form-label">Peso Destino (Ton)</label>
                    <input type="number" step="0.01" name="weight_dest" class="form-input" 
                           value="<?php echo $trip['weight_dest']; ?>">
                </div>
            </div>
            
            <!-- Archivo Manifiesto -->
            <div style="margin-top: 20px;">
                <label class="form-label">Archivo del Manifiesto (PDF/Imagen)</label>
                <?php if ($trip['manifest_file']): ?>
                    <p style="margin-bottom: 8px;">
                        <a href="<?php echo $trip['manifest_file']; ?>" target="_blank" style="color: #3b82f6; text-decoration: none;">
                            📄 Ver archivo actual
                        </a>
                    </p>
                <?php endif; ?>
                <input type="file" name="manifest_file" accept=".pdf,.jpg,.jpeg,.png" class="form-input">
                <input type="hidden" name="existing_manifest_file" value="<?php echo $trip['manifest_file']; ?>">
            </div>
        </div>
        
        <!-- SECCIÓN 5: Deducibles -->
        <div class="form-section">
            <div class="form-section-header">
                <h2 class="form-section-title">Deducibles y Retenciones</h2>
                <p class="form-section-subtitle">Porcentajes de retenciones aplicables</p>
            </div>
            
            <div class="form-grid form-grid-4">
                <div>
                    <label class="form-label">% Rete Fuente</label>
                    <input type="number" step="0.01" name="percent_rete_fuente" class="form-input" 
                           value="<?php echo $trip['percent_rete_fuente'] ?? $config['default_rete_fuente']; ?>">
                </div>
                <div>
                    <label class="form-label">% Rete ICA</label>
                    <input type="number" step="0.01" name="percent_rete_ica" class="form-input" 
                           value="<?php echo $trip['percent_rete_ica'] ?? $config['default_rete_ica']; ?>">
                </div>
                <div>
                    <label class="form-label">% IVA</label>
                    <input type="number" step="0.01" name="percent_iva" class="form-input" 
                           value="<?php echo $trip['percent_iva'] ?? $config['default_iva_percent']; ?>">
                </div>
                <div>
                    <label class="form-label">% Rete IVA</label>
                    <input type="number" step="0.01" name="percent_rete_iva" class="form-input" 
                           value="<?php echo $trip['percent_rete_iva'] ?? $config['default_rete_iva_percent']; ?>">
                </div>
            </div>
            
            <!-- Deducibles adicionales -->
            <div class="form-grid form-grid-3" style="margin-top: 20px;">
                <div>
                    <label class="form-label">% Deducible 3</label>
                    <input type="number" step="0.01" name="percent_deductible_3" class="form-input" 
                           value="<?php echo $trip['percent_deductible_3']; ?>">
                </div>
                <div>
                    <label class="form-label">Valor Deducible 4</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><?php echo $config['currency_symbol'] ?? '$'; ?></span>
                        <input type="number" name="value_deductible_4" class="form-input" 
                               value="<?php echo $trip['value_deductible_4']; ?>" 
                               style="padding-left: 28px;">
                    </div>
                </div>
                <div>
                    <label class="form-label">Valor Deducible 5</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><?php echo $config['currency_symbol'] ?? '$'; ?></span>
                        <input type="number" name="value_deductible_5" class="form-input" 
                               value="<?php echo $trip['value_deductible_5']; ?>" 
                               style="padding-left: 28px;">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SECCIÓN 6: Anticipos -->
        <div class="form-section">
            <div class="form-section-header">
                <h2 class="form-section-title">Anticipos</h2>
                <p class="form-section-subtitle">Control de anticipos y pagos</p>
            </div>
            
            <div class="form-grid form-grid-2">
                <div>
                    <label class="form-label">Anticipo Manifiesto</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><?php echo $config['currency_symbol'] ?? '$'; ?></span>
                        <input type="number" name="advance_manifest" class="form-input" 
                               value="<?php echo $trip['advance_manifest']; ?>" 
                               style="padding-left: 28px;">
                    </div>
                    <label style="display: flex; align-items: center; margin-top: 8px; font-size: 0.875rem;">
                        <input type="checkbox" name="advance_manifest_to_driver" value="1" 
                               <?php echo $trip['advance_manifest_to_driver'] ? 'checked' : ''; ?> 
                               style="margin-right: 8px;">
                        Entregado al conductor
                    </label>
                </div>
                <div>
                    <label class="form-label">Anticipo Propietario</label>
                    <div style="position: flex; align-items: center; margin-top: 8px; font-size: 0.875rem;">
                        <input type="checkbox" name="advance_manifest_to_driver" value="1" 
                               <?php echo $trip['advance_manifest_to_driver'] ? 'checked' : ''; ?> 
                               style="margin-right: 8px;">
                        Entregado al conductor
                    </div>
                </div>
                <div>
                    <label class="form-label">Anticipo Propietario</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%);"><?php echo $config['currency_symbol'] ?? '$'; ?></span>
                        <input type="number" name="advance_owner" class="form-input" 
                               value="<?php echo $trip['advance_owner']; ?>" 
                               style="padding-left: 28px;">
                    </div>
                </div>
            </div>
            
            <!-- Estado de Liquidación -->
            <div style="margin-top: 20px;">
                <label class="form-label">Estado de Liquidación</label>
                <select name="settlement_status" class="form-select" style="max-width: 300px;">
                    <option value="Pending" <?php echo ($trip['settlement_status'] ?? 'Pending') == 'Pending' ? 'selected' : ''; ?>>⏳ Pendiente</option>
                    <option value="Settled" <?php echo ($trip['settlement_status'] ?? '') == 'Settled' ? 'selected' : ''; ?>>✅ Liquidado</option>
                </select>
            </div>
        </div>
        
        <!-- Botones de Acción -->
        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 32px; padding-top: 24px; border-top: 2px solid #e2e8f0;">
            <a href="trip_details.php?id=<?php echo $tripId; ?>" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">💾 Guardar Cambios</button>
        </div>
        
    </form>
</div>

<!-- JavaScript simple para carga de ciudades -->
<script>
// Array de ciudades para filtrado
const cities = <?php echo json_encode($cities); ?>;

function loadCities(type, stateId, selectedCityId) {
    const citySelect = document.getElementById(type + '_city');
    
    // Limpiar select
    citySelect.innerHTML = '<option value="">-- Seleccione Ciudad --</option>';
    
    if (!stateId) return;
    
    // Filtrar ciudades por departamento
    const filteredCities = cities.filter(c => c.state_id == stateId);
    
    // Agregar opciones
    filteredCities.forEach(city => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.name;
        if (selectedCityId && city.id == selectedCityId) {
            option.selected = true;
        }
        citySelect.appendChild(option);
    });
}

// Validación simple del formulario
document.getElementById('tripForm').addEventListener('submit', function(e) {
    const dateLoad = document.querySelector('[name="date_load"]').value;
    const dateUnload = document.querySelector('[name="date_unload"]').value;
    
    if (dateLoad && dateUnload && dateUnload < dateLoad) {
        e.preventDefault();
        alert('❌ La fecha de descarga no puede ser anterior a la fecha de carga');
        return false;
    }
    
    const kmsStart = parseFloat(document.querySelector('[name="kms_start"]').value) || 0;
    const kmsEnd = parseFloat(document.querySelector('[name="kms_end"]').value) || 0;
    
    if (kmsEnd > 0 && kmsEnd < kmsStart) {
        if (!confirm('⚠️ El kilometraje final es menor al inicial. ¿Deseas continuar?')) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

</body>
</html>
