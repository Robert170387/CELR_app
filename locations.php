<?php
require_once 'includes/auth.php';
include 'includes/db.php';

if (!isAuthenticated()) {
    header("Location: login.php");
    exit;
}

// Handle Delete (Soft Delete)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("UPDATE locations SET active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: locations.php?success=deleted");
    exit;
}

// Handle Form Submit (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $country_id = $_POST['country_id'] ?? null;
    $state_id = $_POST['state_id'] ?? null;
    $city_id = $_POST['city_id'] ?? null;
    $id = $_POST['id'] ?? null;

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE locations SET name = ?, country_id = ?, state_id = ?, city_id = ? WHERE id = ?");
        $stmt->execute([$name, $country_id, $state_id, $city_id, $id]);
    } else {
        // Create
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO locations (name, country_id, state_id, city_id, active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$name, $country_id, $state_id, $city_id]);
        }
    }
    header("Location: locations.php?success=saved");
    exit;
}

include 'includes/header.php';

// Fetch Item to Edit if requested
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("
        SELECT l.*, c.name as country_name, s.name as state_name, ci.name as city_name 
        FROM locations l
        LEFT JOIN loc_countries c ON l.country_id = c.id
        LEFT JOIN loc_states s ON l.state_id = s.id
        LEFT JOIN loc_cities ci ON l.city_id = ci.id
        WHERE l.id = ?
    ");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}

// Fetch list of active items
$locations = $pdo->query("
    SELECT l.*, s.name as state_name, ci.name as city_name 
    FROM locations l 
    LEFT JOIN loc_states s ON l.state_id = s.id
    LEFT JOIN loc_cities ci ON l.city_id = ci.id
    WHERE l.active = 1 
    ORDER BY l.name ASC
")->fetchAll();
?>

<div class="max-w-6xl mx-auto py-6 px-4">
    <?php displayAlerts(); ?>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Gestión de Rutas / Ubicaciones</h3>
            <p class="mt-1 text-sm text-gray-600">
                Administra ciudades, puertos y puntos de destino con jerarquía completa.
            </p>

            <form action="locations.php" method="POST" class="mt-6 bg-white p-5 shadow-sm border border-slate-200 rounded-xl"
                  x-data="locationSelector('<?php echo $editItem['country_name'] ?? 'Colombia'; ?>', '<?php echo $editItem['state_name'] ?? ''; ?>', '<?php echo $editItem['city_name'] ?? ''; ?>')"
                  x-init="init()">
                
                <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?php echo $editItem ? 'Editar Punto' : 'Nuevo Punto'; ?>
                </h4>

                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                <?php endif; ?>

                <div class="space-y-4">
                    <!-- Custom Name -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Nombre del Punto / Alias</label>
                        <input type="text" name="name"
                            value="<?php echo $editItem ? htmlspecialchars($editItem['name']) : ''; ?>" required
                            class="block w-full rounded-lg border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                            placeholder="Ej. Puerto Buenaventura">
                    </div>

                    <!-- Country -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">País</label>
                        <select x-model="selectedCountry" @change="onCountryChange()"
                                class="block w-full rounded-lg border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                            <option value="">Seleccione País</option>
                            <template x-for="c in countries" :key="c.id">
                                <option :value="c.name" x-text="c.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="country_id" :value="countries.find(c => c.name === selectedCountry)?.id || ''">
                    </div>

                    <!-- Department -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Departamento</label>
                        <select x-model="selectedState" @change="onStateChange()" :disabled="!selectedCountry || isLoadingStates"
                                class="block w-full rounded-lg border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm disabled:bg-slate-50">
                            <option value="">Seleccione Departamento</option>
                            <template x-for="s in states" :key="s.id">
                                <option :value="s.name" x-text="s.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="state_id" :value="states.find(s => s.name === selectedState)?.id || ''">
                    </div>

                    <!-- City -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Ciudad / Municipio</label>
                        <select x-model="selectedCity" :disabled="!selectedState || isLoadingCities"
                                class="block w-full rounded-lg border-slate-200 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm disabled:bg-slate-50">
                            <option value="">Seleccione Ciudad</option>
                            <template x-for="ci in cities" :key="ci.id">
                                <option :value="ci.name" x-text="ci.name"></option>
                            </template>
                        </select>
                        <input type="hidden" name="city_id" :value="cities.find(ci => ci.name === selectedCity)?.id || ''">
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit"
                            class="flex-1 inline-flex justify-center rounded-lg border border-transparent bg-brand-600 py-2 px-4 text-sm font-bold text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-all">
                            <?php echo $editItem ? 'Actualizar' : 'Guardar'; ?>
                        </button>

                        <?php if ($editItem): ?>
                            <a href="locations.php"
                                class="inline-flex justify-center rounded-lg border border-slate-200 bg-white py-2 px-4 text-sm font-bold text-slate-600 shadow-sm hover:bg-slate-50 transition-all">
                                Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="overflow-hidden shadow-sm border border-slate-200 rounded-xl">
                <table class="min-w-full divide-y divide-slate-200 overflow-hidden">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Ubicación / Punto</th>
                            <th class="px-6 py-4 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Departamento / Ciudad</th>
                            <th class="relative py-4 pl-3 pr-6 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <?php foreach ($locations as $l): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($l['name']); ?></div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars($l['city_name'] ?: '-'); ?></span>
                                        <span class="text-[10px] font-bold text-brand-600 uppercase tracking-wider"><?php echo htmlspecialchars($l['state_name'] ?: '-'); ?></span>
                                    </div>
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-3">
                                        <a href="locations.php?edit=<?php echo $l['id']; ?>"
                                            class="text-indigo-500 hover:text-indigo-700 bg-indigo-50 p-1.5 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <a href="locations.php?delete=<?php echo $l['id']; ?>"
                                            onclick="return confirm('¿Seguro que deseas eliminar esta ubicación?');"
                                            class="text-red-500 hover:text-red-700 bg-red-50 p-1.5 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($locations)): ?>
                            <tr><td colspan="3" class="px-6 py-10 text-center text-sm text-slate-400 italic">No hay ubicaciones registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="js/location_selector.js"></script>
<?php include 'includes/footer.php'; ?>