<?php
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/header.php';

requireRole('admin');

$user = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch();
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <?php include 'includes/admin_nav.php'; ?>

    <div class="max-w-xl mx-auto">
        <h3 class="text-lg font-medium text-gray-900"><?php echo $user ? 'Editar' : 'Nuevo'; ?> Usuario</h3>

        <form action="save_user.php" method="POST" enctype="multipart/form-data"
            class="mt-5 space-y-6 bg-white p-6 shadow sm:rounded-md">
            <?php if ($user): ?>
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700">Avatar / Foto</label>
                <div class="mt-2 flex items-center space-x-4">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar"
                            class="h-12 w-12 rounded-full object-cover">
                    <?php endif; ?>
                    <input type="file" name="avatar" accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                    <input type="text" name="full_name" value="<?php echo $user['full_name'] ?? ''; ?>" required
                        placeholder="Ej: Juan Pérez"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
                    <input type="text" name="username" value="<?php echo $user['username'] ?? ''; ?>" required
                        placeholder="Ej: juanp"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" name="password" <?php echo $user ? '' : 'required'; ?>
                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                <?php if ($user): ?>
                    <p class="mt-1 text-xs text-gray-500">Dejar en blanco para mantener la contraseña actual.</p>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2" x-data="{ 
                role: '<?php echo $user['role'] ?? 'common'; ?>',
                client: '<?php echo $user['related_client_id'] ?? ''; ?>',
                personnel: '<?php echo $user['related_personnel_id'] ?? ''; ?>'
            }">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Rol</label>
                    <select name="role" x-model="role"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="common">Usuario Operativo</option>
                        <option value="admin">Administrador</option>
                        <option value="cliente">Cliente (Portal)</option>
                        <option value="conductor">Conductor (Portal)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="status"
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                        <option value="active" <?php echo ($user['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactive" <?php echo ($user['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>
                            Inactivo</option>
                    </select>
                </div>

                <!-- Conditional Selectors -->
                <div x-show="role === 'cliente'" x-cloak class="sm:col-span-2">
                    <label class="block text-sm font-medium text-brand-700">Asociar a Cliente</label>
                    <select name="related_client_id" x-model="client"
                        class="mt-1 block w-full rounded-md border-brand-300 bg-brand-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm font-bold">
                        <option value="">Seleccione Cliente...</option>
                        <?php
                        $clients = $pdo->query("SELECT id, person_type, business_name, firstname, lastname1 FROM clients WHERE active = 1 ORDER BY id DESC")->fetchAll();
                        foreach ($clients as $c):
                            $name = ($c['person_type'] === 'Jurídica' ? $c['business_name'] : $c['firstname'] . ' ' . $c['lastname1']);
                            ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($name); ?> (ID:
                                <?php echo $c['id']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-brand-600 italic">Este usuario solo verá viajes asociados a este
                        cliente.</p>
                </div>

                <div x-show="role === 'conductor'" x-cloak class="sm:col-span-2">
                    <label class="block text-sm font-medium text-brand-700">Asociar a Conductor / Personal</label>
                    <select name="related_personnel_id" x-model="personnel"
                        class="mt-1 block w-full rounded-md border-brand-300 bg-brand-50 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm font-bold">
                        <option value="">Seleccione Personal...</option>
                        <?php
                        $staff = $pdo->query("SELECT id, firstname, lastname, type FROM personnel WHERE active = 1 ORDER BY firstname ASC")->fetchAll();
                        foreach ($staff as $s):
                            ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['firstname'] . ' ' . $s['lastname']); ?>
                                (<?php echo $s['type']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-brand-600 italic">Este usuario solo verá viajes asignados a él y sus
                        liquidaciones.</p>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <a href="users.php"
                    class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 mr-3">Cancelar</a>
                <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>