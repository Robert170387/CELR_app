<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="mb-8 border-b border-gray-200">
    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <a href="config.php"
            class="<?php echo $current == 'config.php' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
            General
        </a>

        <a href="users.php"
            class="<?php echo $current == 'users.php' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
            Usuarios
        </a>

        <a href="categories.php"
            class="<?php echo $current == 'categories.php' || $current == 'category_form.php' ? 'border-brand-500 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
            Categorías de Gastos
        </a>
    </nav>
</div>