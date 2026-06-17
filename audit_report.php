<?php
include 'includes/db.php';
include 'includes/functions.php';
include 'includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$users = $pdo->query("SELECT id, username, full_name FROM users ORDER BY username ASC")->fetchAll();

$where = ["1=1"];
$params = [];
if (!empty($_GET['user_id'])) {
    $where[] = "a.user_id = ?";
    $params[] = $_GET['user_id'];
}
if (!empty($_GET['entity_type'])) {
    $where[] = "a.entity_type = ?";
    $params[] = $_GET['entity_type'];
}
if (!empty($_GET['action'])) {
    $where[] = "a.action = ?";
    $params[] = $_GET['action'];
}

$whereClause = implode(" AND ", $where);
$logs = $pdo->prepare("SELECT a.*, u.username, u.full_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE $whereClause ORDER BY a.created_at DESC LIMIT 100");
$logs->execute($params);
$logEntries = $logs->fetchAll();

function formatJsonDiff($old, $new)
{
    if (!$old || !$new)
        return null;
    $oldArr = json_decode($old, true);
    $newArr = json_decode($new, true);
    if (!$oldArr || !$newArr)
        return null;
    $diff = [];
    foreach ($newArr as $key => $val) {
        if (isset($oldArr[$key]) && $oldArr[$key] != $val) {
            if (in_array($key, ['amount', 'flete_neto', 'kms_end', 'status', 'settlement_status'])) {
                $diff[$key] = ['old' => $oldArr[$key], 'new' => $val];
            }
        }
    }
    return $diff;
}
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between py-2">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center">
                <span class="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                </span>
                Bitácora de Auditoría
            </h1>
            <p class="text-sm font-semibold text-slate-500">Centro de monitoreo de integridad y seguridad de datos.</p>
        </div>
    </div>

    <!-- Filter Console -->
    <div
        class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6 gap-6 grid grid-cols-1 md:grid-cols-4 items-end">
        <form method="GET" class="contents">
            <div>
                <label class="saas-label">Usuario Operador</label>
                <select name="user_id" class="saas-input">
                    <option value="">Cualquier usuario</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($_GET['user_id'] ?? '') == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['full_name'] ?: $u['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="saas-label">Módulo Crítico</label>
                <select name="entity_type" class="saas-input">
                    <option value="">Todos los módulos</option>
                    <option value="TRIP" <?php echo ($_GET['entity_type'] ?? '') == 'TRIP' ? 'selected' : ''; ?>>
                        Operaciones (Viajes)</option>
                    <option value="EXPENSE" <?php echo ($_GET['entity_type'] ?? '') == 'EXPENSE' ? 'selected' : ''; ?>>
                        Finanzas (Gastos)</option>
                </select>
            </div>
            <div>
                <label class="saas-label">Tipo de Acción</label>
                <select name="action" class="saas-input">
                    <option value="">Todas las acciones</option>
                    <option value="CREATE" <?php echo ($_GET['action'] ?? '') == 'CREATE' ? 'selected' : ''; ?>>Creación
                    </option>
                    <option value="UPDATE" <?php echo ($_GET['action'] ?? '') == 'UPDATE' ? 'selected' : ''; ?>>
                        Modificación</option>
                    <option value="DELETE" <?php echo ($_GET['action'] ?? '') == 'DELETE' ? 'selected' : ''; ?>>
                        Eliminación</option>
                </select>
            </div>
            <button type="submit" class="btn-saas btn-saas-primary w-full shadow-lg shadow-brand-500/20">Ejecutar
                Auditoría</button>
        </form>
    </div>

    <!-- Log Console -->
    <div class="bg-slate-900 rounded-3xl overflow-hidden shadow-2xl border border-slate-800">
        <div class="px-6 py-4 bg-slate-800/50 border-b border-slate-700 flex items-center space-x-2">
            <span class="w-3 h-3 rounded-full bg-red-500"></span>
            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
            <span
                class="ml-4 text-xs font-mono text-slate-400 font-bold uppercase tracking-widest">audit_console_system.v1.0</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-800/30">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">timestamp
                            / node</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">actor</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            transaction</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">payload
                            diff</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($logEntries as $log): ?>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-6 whitespace-nowrap">
                                <p class="text-[11px] font-mono text-emerald-400 font-bold leading-none">
                                    <?php echo $log['created_at']; ?></p>
                                <p class="text-[10px] font-mono text-slate-500 mt-2">IP: <?php echo $log['ip_address']; ?>
                                </p>
                            </td>
                            <td class="px-6 py-6">
                                <div class="flex items-center">
                                    <div
                                        class="h-7 w-7 rounded bg-brand-600 text-white flex items-center justify-center text-[10px] font-black mr-3 shadow-lg shadow-brand-500/10">
                                        <?php echo substr($log['username'], 0, 1); ?>
                                    </div>
                                    <span
                                        class="text-xs font-bold text-slate-200"><?php echo htmlspecialchars($log['full_name'] ?: $log['username']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-6">
                                <?php
                                $actionColor = match ($log['action']) {
                                    'CREATE' => 'text-emerald-400 bg-emerald-400/10',
                                    'UPDATE' => 'text-blue-400 bg-blue-400/10',
                                    'DELETE' => 'text-red-400 bg-red-400/10',
                                    default => 'text-slate-400 bg-slate-400/10'
                                };
                                ?>
                                <span
                                    class="text-[10px] font-black px-2 py-1 rounded border border-transparent <?php echo $actionColor; ?> uppercase tracking-tighter">
                                    <?php echo $log['action']; ?>_<?php echo $log['entity_type']; ?>
                                    #<?php echo $log['entity_id']; ?>
                                </span>
                                <p class="text-[10px] text-slate-500 mt-2 italic font-medium">
                                    <?php echo htmlspecialchars($log['details']); ?></p>
                            </td>
                            <td class="px-6 py-6 min-w-[280px]">
                                <?php
                                $diff = formatJsonDiff($log['old_values'], $log['new_values']);
                                if ($diff): ?>
                                    <div class="space-y-2 border-l border-slate-700 pl-4 py-1">
                                        <?php foreach ($diff as $field => $change): ?>
                                            <div class="text-[10px] font-mono leading-tight">
                                                <span class="text-slate-500 uppercase"><?php echo $field; ?>:</span>
                                                <span class="text-red-500/80 line-through mx-1"><?php echo $change['old']; ?></span>
                                                <span class="text-emerald-400 font-bold ml-1">→ <?php echo $change['new']; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($log['action'] == 'UPDATE'): ?>
                                    <span
                                        class="text-[10px] font-mono text-slate-600 italic font-medium">metadata_only_change</span>
                                <?php else: ?>
                                    <span
                                        class="text-[10px] font-mono text-slate-600 italic font-medium">initial_creation_snapshot</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>