<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'includes/db.php';

$dateFrom  = $_GET['date_from']  ?? '';
$dateTo    = $_GET['date_to']    ?? '';
$vehicleId = (int)($_GET['vehicle_id'] ?? 0);
$statusF   = $_GET['status'] ?? '';

$where = ["1=1"]; $params = [];
if ($dateFrom) { $where[] = "t.date_load >= :df"; $params[':df'] = $dateFrom; }
if ($dateTo)   { $where[] = "t.date_load <= :dt"; $params[':dt'] = $dateTo; }
if ($vehicleId){ $where[] = "t.vehicle_id = :vid"; $params[':vid'] = $vehicleId; }
if ($statusF)  { $where[] = "t.status = :st";  $params[':st'] = $statusF; }

$sql = "SELECT t.id, t.date_load, t.origin, t.destination, t.flete_bruto, t.flete_neto,
               t.status, t.manifest_number,
               v.placa AS vehicle_placa,
               p.full_name AS driver_name
        FROM trips t
        LEFT JOIN vehicles v ON v.id = t.vehicle_id
        LEFT JOIN personnel p ON p.id = t.driver_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY t.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

$vehicles = $pdo->query("SELECT id, placa FROM vehicles ORDER BY placa")->fetchAll();
$total_flete = array_sum(array_column($trips, 'flete_bruto'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CELR App - Registro de Viajes</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="index.css?v=1783444448">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{50:'#f0f4ff',100:'#e0e9fe',500:'#355df5',600:'#223de9',700:'#1b2ecf'}}}}}</script>
</head>
<body class="bg-slate-50 h-screen overflow-hidden text-slate-900">
<div class="flex h-screen overflow-hidden">
<div class="flex flex-col w-64 bg-slate-950 border-r border-slate-800">
<div class="flex items-center justify-center px-8 py-10 border-b border-slate-800/50">
<div class="w-10 h-10 bg-brand-600 rounded-lg flex items-center justify-center mr-4">
<svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
</div>
<h1 class="text-xl font-bold text-white uppercase">CELR <span class="text-brand-500">App</span></h1>
</div>
<nav class="flex-grow py-6 overflow-y-auto px-4">
<a href="index.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-900 rounded-xl mb-1">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
Dashboard</a>
<div x-data="{open:true}" class="mb-1">
<button @click="open=!open" class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-medium rounded-xl text-white bg-slate-900 border border-slate-800">
<span class="flex items-center"><svg class="w-5 h-5 mr-3 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>GestiÃ³n Operativa</span>
<svg class="w-4 h-4 transition-transform" :class="{'rotate-90':open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
</button>
<div x-show="open" class="mt-1 space-y-1 bg-slate-900/50 rounded-xl p-1 border border-slate-800/30">
<a href="trips.php" class="flex items-center px-4 py-2 text-xs font-semibold rounded-lg text-white bg-brand-600"><span class="w-1.5 h-1.5 rounded-full mr-3 bg-white"></span>Registro de Viajes</a>
<a href="rndc.php" class="flex items-center px-4 py-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-200 hover:bg-slate-800"><span class="w-1.5 h-1.5 rounded-full mr-3 bg-slate-700"></span>Manifiestos RNDC</a>
<a href="trip_advances.php" class="flex items-center px-4 py-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-200 hover:bg-slate-800"><span class="w-1.5 h-1.5 rounded-full mr-3 bg-slate-700"></span>Anticipos</a>
<a href="fuel_vouchers.php" class="flex items-center px-4 py-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-200 hover:bg-slate-800"><span class="w-1.5 h-1.5 rounded-full mr-3 bg-slate-700"></span>Combustible</a>
<a href="expenses.php" class="flex items-center px-4 py-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-200 hover:bg-slate-800"><span class="w-1.5 h-1.5 rounded-full mr-3 bg-slate-700"></span>Gastos de Viaje</a>
<a href="settlements.php" class="flex items-center px-4 py-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-200 hover:bg-slate-800"><span class="w-1.5 h-1.5 rounded-full mr-3 bg-slate-700"></span>Liquidaciones</a>
<a href="import_trips.php" class="flex items-center px-4 py-2 text-xs font-semibold rounded-lg text-slate-500 hover:text-slate-200 hover:bg-slate-800"><span class="w-1.5 h-1.5 rounded-full mr-3 bg-slate-700"></span>Importar CSV</a>
</div></div>
<a href="maintenance_list.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-900 rounded-xl mb-1">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
Mantenimiento</a>
<a href="financial_report.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-900 rounded-xl mb-1">
<svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
AnalÃ­tica</a>
</nav></div>
<div class="flex-1 flex flex-col overflow-hidden">
<header class="flex justify-between items-center py-4 px-8 bg-white/80 backdrop-blur-md border-b border-slate-200 z-10">
<h2 class="text-xl font-bold text-slate-800">Registro de Viajes</h2>
<div x-data="{open:false}" @click.away="open=false" class="relative">
<button @click="open=!open" class="flex items-center rounded-xl focus:outline-none">
<div class="mr-3 text-right hidden sm:block">
<p class="text-sm font-bold text-slate-900"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></p>
<p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></p>
</div>
<div class="h-9 w-9 rounded-xl bg-slate-900 text-white flex items-center justify-center text-xs font-bold">
<?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)) ?>
</div></button>
<div x-show="open" class="absolute right-0 mt-3 w-44 z-50" style="display:none">
<div class="rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/5 overflow-hidden py-2">
<a href="logout.php" class="flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
<svg class="mr-3 h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
Cerrar SesiÃ³n</a>
</div></div></div>
</header>
<main class="flex-1 overflow-x-hidden overflow-y-auto p-8">
<div class="max-w-7xl mx-auto">
<div class="sm:flex sm:items-center mb-6">
<div class="sm:flex-auto">
<h1 class="text-xl font-semibold text-gray-900">Registro de Viajes</h1>
<p class="mt-1 text-sm text-gray-500">Historial de viajes registrados en el sistema.</p>
</div>
<div class="mt-4 sm:mt-0 sm:ml-16">
<a href="trip_form.php" class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">+ Nuevo Viaje</a>
</div></div>
<div class="flex flex-wrap gap-4 mb-8">
<div class="inline-flex items-center bg-brand-50 border border-brand-100 px-4 py-2 rounded-lg">
<span class="text-xs font-bold text-brand-600 uppercase tracking-widest mr-3">Total Viajes:</span>
<span class="text-xl font-bold text-slate-900"><?= count($trips) ?></span>
</div>
<?php if ($total_flete > 0): ?>
<div class="inline-flex items-center bg-green-50 border border-green-100 px-4 py-2 rounded-lg">
<span class="text-xs font-bold text-green-600 uppercase tracking-widest mr-3">Flete Bruto Total:</span>
<span class="text-xl font-bold text-slate-900">$ <?= number_format($total_flete,2,',','.') ?></span>
</div>
<?php endif; ?>
</div>
<div class="mb-8 bg-white border border-gray-200 rounded-lg shadow-sm" x-data="{open:false}">
<div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between cursor-pointer" @click="open=!open">
<h3 class="text-sm font-bold text-gray-700">Filtros</h3>
<span class="text-xs text-gray-400" x-text="open?'Cerrar':'Abrir'"></span>
</div>
<div x-show="open">
<form method="GET" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4">
<div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Desde</label>
<input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="block w-full border-gray-300 rounded-md shadow-sm text-xs"></div>
<div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hasta</label>
<input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="block w-full border-gray-300 rounded-md shadow-sm text-xs"></div>
<div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">VehÃ­culo</label>
<select name="vehicle_id" class="block w-full border-gray-300 rounded-md shadow-sm text-xs">
<option value="">Todos</option>
<?php foreach($vehicles as $v): ?>
<option value="<?= $v['id'] ?>" <?= $vehicleId==$v['id']?'selected':'' ?>><?= htmlspecialchars($v['placa']) ?></option>
<?php endforeach; ?>
</select></div>
<div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Estado</label>
<select name="status" class="block w-full border-gray-300 rounded-md shadow-sm text-xs">
<option value="">Todos</option>
<option value="active" <?= $statusF==='active'?'selected':'' ?>>Activo</option>
<option value="completed" <?= $statusF==='completed'?'selected':'' ?>>Completado</option>
<option value="settled" <?= $statusF==='settled'?'selected':'' ?>>Liquidado</option>
<option value="cancelled" <?= $statusF==='cancelled'?'selected':'' ?>>Cancelado</option>
</select></div>
<div class="md:col-span-4 flex justify-end space-x-3">
<a href="trips.php" class="text-xs text-gray-500 font-medium py-2">Limpiar</a>
<button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-xs font-bold">Filtrar</button>
</div></form></div></div>
<div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
<table class="min-w-full divide-y divide-gray-300">
<thead class="bg-gray-50">
<tr>
<th class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-900 sm:pl-6">Fecha Cargue</th>
<th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">VehÃ­culo</th>
<th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Conductor</th>
<th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Origen / Destino</th>
<th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Manifiesto</th>
<th class="px-3 py-3.5 text-right text-xs font-semibold text-gray-900">Flete Bruto</th>
<th class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900">Estado</th>
<th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Acciones</span></th>
</tr>
</thead>
<tbody class="divide-y divide-gray-200 bg-white">
<?php if(empty($trips)): ?>
<tr><td colspan="8" class="px-6 py-12 text-center text-sm text-gray-400">No hay viajes registrados.</td></tr>
<?php else: foreach($trips as $t):
$sc = ['active'=>'bg-blue-100 text-blue-800','completed'=>'bg-green-100 text-green-800',
       'settled'=>'bg-purple-100 text-purple-800','cancelled'=>'bg-red-100 text-red-800'];
$sl = ['active'=>'Activo','completed'=>'Completado','settled'=>'Liquidado','cancelled'=>'Cancelado'];
$st = $t['status'] ?? '';
?>
<tr class="hover:bg-gray-50">
<td class="whitespace-nowrap py-4 pl-4 pr-3 text-xs font-mono text-gray-400 sm:pl-6">
<?= $t['date_load'] ? date('d M Y', strtotime($t['date_load'])) : 'â€”' ?>
</td>
<td class="whitespace-nowrap px-3 py-4 text-sm font-bold text-gray-900 uppercase"><?= htmlspecialchars($t['vehicle_placa'] ?? 'â€”') ?></td>
<td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700"><?= htmlspecialchars($t['driver_name'] ?? 'â€”') ?></td>
<td class="px-3 py-4 text-sm">
<p class="font-semibold text-gray-700"><?= htmlspecialchars($t['origin'] ?? 'â€”') ?></p>
<p class="text-xs text-gray-400">â†’ <?= htmlspecialchars($t['destination'] ?? '') ?></p>
</td>
<td class="whitespace-nowrap px-3 py-4 text-xs text-gray-500"><?= htmlspecialchars($t['manifest_number'] ?? 'â€”') ?></td>
<td class="whitespace-nowrap px-3 py-4 text-sm text-right font-bold text-green-700">
$ <?= number_format($t['flete_bruto'] ?? 0, 2, ',', '.') ?>
</td>
<td class="whitespace-nowrap px-3 py-4">
<span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold <?= $sc[$st] ?? 'bg-gray-100 text-gray-800' ?>">
<?= $sl[$st] ?? ucfirst($st) ?></span>
</td>
<td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm sm:pr-6">
<div class="flex items-center justify-end space-x-3">
<a href="trip_details.php?id=<?= $t['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Ver">
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
</a>
<a href="trip_form.php?id=<?= $t['id'] ?>" class="text-indigo-600 hover:text-indigo-900" title="Editar">
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
</a>
</div></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table></div></div></main></div></div>
</body></html>
