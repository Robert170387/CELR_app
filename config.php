<?php
include 'includes/db.php';
include 'includes/header.php';

// Protect Admin Route
requireRole('admin');

// Fetch current config (with caching)
require_once 'app/Core/Cache.php';
use App\Core\Cache;

$config = Cache::remember('system_config', function() use ($pdo) {
    $stmt = $pdo->query("SELECT * FROM config LIMIT 1");
    return $stmt->fetch();
}, 300); // Cache for 5 minutes
?>

<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <?php include 'includes/admin_nav.php'; ?>

    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Configuración del Sistema</h1>
        <p class="text-sm font-medium text-slate-500 mt-1">Parámetros esenciales para cálculos financieros y operativos.
        </p>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div
            class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center text-emerald-700 text-sm font-bold shadow-sm">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Configuración actualizada correctamente.
        </div>
    <?php endif; ?>

    <form action="save_config.php" method="POST" enctype="multipart/form-data" class="space-y-12">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="config_id" value="<?php echo $config['id']; ?>">

        <!-- 1. IDENTIDAD Y MARCA -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                <h3 class="text-lg font-bold text-slate-900 flex items-center">
                    <span class="w-2 h-6 bg-brand-500 rounded-full mr-3"></span>
                    Identidad de la Empresa
                </h3>
            </div>
            <div class="p-8 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Logo
                            Principal</label>
                        <div class="flex items-center space-x-6">
                            <div
                                class="h-24 w-48 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden">
                                <?php if (!empty($config['logo_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($config['logo_path']); ?>"
                                        class="h-full w-full object-contain">
                                <?php else: ?>
                                    <span class="text-[10px] font-bold text-slate-400">Sin Logo</span>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="logo"
                                class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Razón Social</label>
                            <input type="text" name="business_name"
                                value="<?php echo htmlspecialchars($config['business_name'] ?? ''); ?>"
                                class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold text-slate-900">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">NIT / Tax ID</label>
                            <input type="text" name="nit" value="<?php echo htmlspecialchars($config['nit'] ?? ''); ?>"
                                class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold text-slate-900">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Dirección</label>
                        <input type="text" name="address"
                            value="<?php echo htmlspecialchars($config['address'] ?? ''); ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Teléfono</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($config['phone'] ?? ''); ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email Corporativo</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($config['email'] ?? ''); ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Resolución de
                        Facturación</label>
                    <textarea name="billing_resolution" rows="2"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-xs"><?php echo htmlspecialchars($config['billing_resolution'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- 2. LOCALIZACIÓN Y DIVISA -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-50 bg-slate-50/30">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center uppercase tracking-widest">
                        Localization & Currency
                    </h3>
                </div>
                <div class="p-6 grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">País Base</label>
                        <input type="text" name="base_country"
                            value="<?php echo htmlspecialchars($config['base_country']); ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Divisa
                            (Código)</label>
                        <input type="text" name="currency" value="<?php echo htmlspecialchars($config['currency']); ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Símbolo</label>
                        <input type="text" name="currency_symbol"
                            value="<?php echo htmlspecialchars($config['currency_symbol']); ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm font-bold text-center">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Sep. Miles</label>
                        <input type="text" name="thousands_separator"
                            value="<?php echo htmlspecialchars($config['thousands_separator']); ?>" maxlength="1"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm text-center font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Sep. Decimal</label>
                        <input type="text" name="decimal_separator"
                            value="<?php echo htmlspecialchars($config['decimal_separator']); ?>" maxlength="1"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm text-center font-bold">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Cantidad de
                            Decimales</label>
                        <input type="number" name="decimal_count"
                            value="<?php echo htmlspecialchars($config['decimal_count']); ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm font-bold">
                        <p class="text-[9px] text-slate-400 mt-1 italic">Usa 0 para COP, 2 para USD/EUR.</p>
                    </div>
                </div>
            </div>

            <!-- 3. CONFIGURACIÓN FISCAL -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-50 bg-slate-50/30">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center uppercase tracking-widest">
                        Estructura Fiscal (%)
                    </h3>
                </div>
                <div class="p-6 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Rete Fuente</label>
                        <input type="number" step="0.01" name="default_rete_fuente"
                            value="<?php echo $config['default_rete_fuente']; ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm font-black text-brand-600">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Rete ICA
                            (Promedio)</label>
                        <input type="number" step="0.01" name="default_rete_ica"
                            value="<?php echo $config['default_rete_ica']; ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm font-black text-brand-600">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">IVA General</label>
                        <input type="number" step="0.01" name="default_iva_percent"
                            value="<?php echo $config['default_iva_percent']; ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm font-black text-emerald-600">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Rete IVA</label>
                        <input type="number" step="0.01" name="default_rete_iva_percent"
                            value="<?php echo $config['default_rete_iva_percent']; ?>"
                            class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm font-black text-emerald-600">
                    </div>
                    <div class="col-span-2 pt-4">
                        <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100 italic">
                            <p class="text-[10px] text-blue-700">Nota: Estos son valores por defecto. Se pueden ajustar
                                individualmente al crear un viaje.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. PARÁMETROS OPERATIVOS -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                <h3 class="text-lg font-bold text-slate-900 flex items-center">
                    <span class="w-2 h-6 bg-amber-500 rounded-full mr-3"></span>
                    Métricas Operativas
                </h3>
            </div>
            <div class="p-8 grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Unidad de Peso</label>
                    <input type="text" name="weight_unit"
                        value="<?php echo htmlspecialchars($config['weight_unit']); ?>" placeholder="ej: Toneladas"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Unidad Distancia</label>
                    <input type="text" name="distance_unit"
                        value="<?php echo htmlspecialchars($config['distance_unit']); ?>" placeholder="ej: Kilómetros"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Alerta Mant. (Kms)</label>
                    <input type="number" name="maint_warning_kms"
                        value="<?php echo htmlspecialchars($config['maint_warning_kms']); ?>"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold text-amber-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Alerta Docs (Días)</label>
                    <input type="number" name="doc_warning_days"
                        value="<?php echo htmlspecialchars($config['doc_warning_days']); ?>"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold text-rose-600">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Comisión Nacional (%)</label>
                    <input type="number" step="0.01" name="ganancia_nacional_percent"
                        value="<?php echo $config['ganancia_nacional_percent']; ?>"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Comisión Urbana (%)</label>
                    <input type="number" step="0.01" name="ganancia_urbano_percent"
                        value="<?php echo $config['ganancia_urbano_percent']; ?>"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Umbral de Gasto Inusual (Alerta)</label>
                    <input type="number" step="0.01" name="unusual_expense_threshold"
                        value="<?php echo htmlspecialchars($config['unusual_expense_threshold'] ?? '1000000'); ?>"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-bold text-brand-700">
                    <p class="mt-1 text-[10px] text-slate-400 italic">Los gastos que igualen o superen este valor generarán una alerta de seguridad en el dashboard.</p>
                </div>
            </div>
        </div>

        <!-- 5. INTEGRACIÓN SATRACK -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                <h3 class="text-lg font-bold text-slate-900 flex items-center">
                    <span class="w-2 h-6 bg-brand-600 rounded-full mr-3"></span>
                    Integración SATRACK (GPS)
                </h3>
            </div>
            <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-widest">Token de
                        Acceso</label>
                    <input type="password" name="satrack_token"
                        value="<?php echo htmlspecialchars($config['satrack_token'] ?? ''); ?>"
                        placeholder="Ingrese el Token de SATRACK"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-widest">API Key</label>
                    <input type="password" name="satrack_api_key"
                        value="<?php echo htmlspecialchars($config['satrack_api_key'] ?? ''); ?>"
                        placeholder="Ingrese la API Key"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-medium text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2 tracking-widest">Endpoint API
                        (URL)</label>
                    <input type="text" name="satrack_api_url"
                        value="<?php echo htmlspecialchars($config['satrack_api_url'] ?? 'https://api.satrack.com/v1/locations'); ?>"
                        class="w-full bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-brand-500 font-medium text-xs">
                    <p class="mt-2 text-[10px] text-slate-400 italic">Configure estas credenciales para habilitar el
                        rastreo
                        satelital en el mapa del dashboard.</p>
                </div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="flex items-center justify-between p-8 bg-slate-900 rounded-3xl shadow-xl shadow-slate-900/20">
            <div>
                <p class="text-white font-bold">Guardar Cambios</p>
                <p class="text-[10px] text-slate-400 font-medium">Se aplicarán a todos los nuevos registros.</p>
            </div>
            <button type="submit"
                class="inline-flex justify-center py-4 px-10 border border-transparent shadow-sm text-sm font-black rounded-2xl text-white bg-brand-500 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all hover:scale-105">
                ACTUALIZAR CONFIGURACIÓN
            </button>
        </div>
    </form>

    <!-- 5. SEGURIDAD (Backup) -->
    <div class="mt-12">
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-bold text-slate-900">Seguridad y Respaldo</h3>
                    <p class="mt-1 text-xs font-medium text-slate-500">
                        Gestiona las copias de seguridad de la base de datos para prevenir pérdida de información.
                    </p>
                </div>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div
                    class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Estado del Servidor
                        </p>
                        <p class="text-sm font-bold text-slate-800">
                            Último respaldo:
                            <span
                                class="<?php echo (strtotime($config['last_backup_at']) < strtotime('-7 days')) ? 'text-red-500' : 'text-emerald-500'; ?>">
                                <?php echo $config['last_backup_at'] ? date('d M, Y - H:i', strtotime($config['last_backup_at'])) : 'Nunca'; ?>
                            </span>
                        </p>
                    </div>
                    <a href="backup_db.php"
                        class="inline-flex items-center px-6 py-3 border border-slate-200 text-xs font-black rounded-2xl text-slate-700 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-width="2" />
                        </svg>
                        GENERAR SQL
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>