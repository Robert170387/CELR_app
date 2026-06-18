<?php
/**
 * Formulario Socio — Crear / Editar
 */
include 'includes/db.php';
include 'includes/header.php';
require_once 'includes/functions.php';

$csrf_token = getCsrfToken();
$editMode   = isset($_GET['id']);
$socioId    = $editMode ? (int)$_GET['id'] : 0;

if ($editMode) {
    $stmt = $pdo->prepare("SELECT * FROM socios WHERE id = ?");
    $stmt->execute([$socioId]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$s) die("Socio #$socioId no encontrado.");
} else {
    $s = [
        'id'                  => null,
        'tipo'                => 'Persona Natural',
        'nombre'              => '',
        'documento'           => '',
        'tipo_documento'      => 'CC',
        'telefono'            => '',
        'celular'             => '',
        'email'               => '',
        'direccion'           => '',
        'ciudad'              => '',
        'departamento'        => '',
        'banco'               => '',
        'cuenta_bancaria'     => '',
        'tipo_cuenta'         => '',
        'porcentaje_utilidad' => '0.00',
        'notas'               => '',
        'active'              => 1,
    ];
}

$msg = $_GET['msg'] ?? '';
?>

<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="socios.php" class="text-gray-400 hover:text-gray-600">← Socios</a>
        <span class="text-gray-300">/</span>
        <h2 class="text-xl font-bold text-gray-900">
            <?php echo $editMode ? 'Editar Socio' : 'Nuevo Socio'; ?>
        </h2>
    </div>

    <?php if ($msg === 'saved'): ?>
        <div class="alert-card alert-card-success">
            <span class="alert-card-icon">✅</span>
            <div class="alert-card-body">
                <div class="alert-card-title">Éxito</div>
                <div class="alert-card-text">Socio guardado. <a href="socios.php" class="ml-2 underline">Ver directorio</a></div>
            </div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert-card alert-card-danger">
            <span class="alert-card-icon">❌</span>
            <div class="alert-card-body">
                <div class="alert-card-title">Error</div>
                <div class="alert-card-text">Por favor revise los datos e intente nuevamente.</div>
            </div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>
    <?php endif; ?>

    <form action="save_socio.php" method="POST" class="bg-white shadow rounded-lg p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="socio_id" value="<?php echo $socioId; ?>">

        <!-- Datos principales -->
        <div>
            <p class="text-sm font-semibold text-gray-700 mb-3">Datos principales</p>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select name="tipo" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="Persona Natural" <?php echo $s['tipo'] === 'Persona Natural' ? 'selected' : ''; ?>>Persona Natural</option>
                        <option value="Empresa" <?php echo $s['tipo'] === 'Empresa' ? 'selected' : ''; ?>>Empresa</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">% Utilidad</label>
                    <input type="number" name="porcentaje_utilidad" step="0.01" min="0" max="100"
                           value="<?php echo htmlspecialchars($s['porcentaje_utilidad']); ?>"
                           placeholder="0.00"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="nombre" required
                       value="<?php echo htmlspecialchars($s['nombre']); ?>"
                       placeholder="Nombre completo o razón social"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento</label>
                    <select name="tipo_documento" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <?php foreach (['CC','NIT','CE','Pasaporte'] as $td): ?>
                            <option value="<?php echo $td; ?>" <?php echo $s['tipo_documento'] === $td ? 'selected' : ''; ?>><?php echo $td; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de Documento</label>
                    <input type="text" name="documento"
                           value="<?php echo htmlspecialchars($s['documento'] ?? ''); ?>"
                           placeholder="1.234.567.890"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
            </div>
        </div>

        <!-- Ubicación y contacto -->
        <div class="border-t pt-4">
            <p class="text-sm font-semibold text-gray-700 mb-3">Ubicación y contacto</p>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Celular</label>
                    <input type="text" name="celular"
                           value="<?php echo htmlspecialchars($s['celular'] ?? ''); ?>"
                           placeholder="310 123 4567"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono fijo</label>
                    <input type="text" name="telefono"
                           value="<?php echo htmlspecialchars($s['telefono'] ?? ''); ?>"
                           placeholder="(4) 123 4567"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email"
                       value="<?php echo htmlspecialchars($s['email'] ?? ''); ?>"
                       placeholder="socio@ejemplo.com"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <input type="text" name="direccion"
                       value="<?php echo htmlspecialchars($s['direccion'] ?? ''); ?>"
                       placeholder="Cra 50 # 30-45, Barrio..."
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input type="text" name="ciudad"
                           value="<?php echo htmlspecialchars($s['ciudad'] ?? ''); ?>"
                           placeholder="Medellín, Bogotá..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                    <input type="text" name="departamento"
                           value="<?php echo htmlspecialchars($s['departamento'] ?? ''); ?>"
                           placeholder="Antioquia, Cundinamarca..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <!-- Datos bancarios -->
        <div class="border-t pt-4">
            <p class="text-sm font-semibold text-gray-700 mb-3">Datos bancarios</p>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                <input type="text" name="banco"
                       value="<?php echo htmlspecialchars($s['banco'] ?? ''); ?>"
                       placeholder="Bancolombia, Davivienda, Nequi..."
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de Cuenta</label>
                    <input type="text" name="cuenta_bancaria"
                           value="<?php echo htmlspecialchars($s['cuenta_bancaria'] ?? ''); ?>"
                           placeholder="123456789"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cuenta</label>
                    <select name="tipo_cuenta" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">-- Seleccione --</option>
                        <option value="Ahorros"   <?php echo ($s['tipo_cuenta'] ?? '') === 'Ahorros'   ? 'selected' : ''; ?>>Ahorros</option>
                        <option value="Corriente" <?php echo ($s['tipo_cuenta'] ?? '') === 'Corriente' ? 'selected' : ''; ?>>Corriente</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Notas -->
        <div class="border-t pt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
            <textarea name="notas" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                      placeholder="Observaciones, acuerdos especiales..."><?php echo htmlspecialchars($s['notas'] ?? ''); ?></textarea>
        </div>

        <?php if ($editMode): ?>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="active" value="1" id="active"
                   <?php echo $s['active'] ? 'checked' : ''; ?>>
            <label for="active" class="text-sm text-gray-700">Socio activo</label>
        </div>
        <?php endif; ?>

        <!-- Botones -->
        <div class="flex justify-end gap-3 pt-2 border-t">
            <a href="socios.php" class="px-5 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded text-sm font-semibold hover:bg-blue-700">
                💾 Guardar Socio
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
