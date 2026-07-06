<?php
// migrations/059_redesign_expense_categories_structure.php

try {
    echo "Starting restructuring of expense categories...\n";

    // 1. Define parent categories
    $parents = [
        'administrativos' => [
            'name' => 'Administrativos',
            'type' => 'fixed'
        ],
        'personal_operativo' => [
            'name' => 'Personal Operativo',
            'type' => 'fixed'
        ],
        'costos_operativos_ruta' => [
            'name' => 'Costos Operativos de Ruta (Viaje)',
            'type' => 'variable'
        ],
        'mantenimiento_activos' => [
            'name' => 'Mantenimiento y Activos (Fijos/Variables)',
            'type' => 'fixed'
        ]
    ];

    $parentIds = [];

    // Ensure parents exist and get their IDs
    foreach ($parents as $slug => $info) {
        $stmt = $pdo->prepare("SELECT id FROM expense_categories WHERE slug = ? AND parent_id IS NULL");
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            $stmtInsert = $pdo->prepare("INSERT INTO expense_categories (name, slug, type, parent_id) VALUES (?, ?, ?, NULL)");
            $stmtInsert->execute([$info['name'], $slug, $info['type']]);
            $id = $pdo->lastInsertId();
            echo "Created parent category '{$info['name']}' (ID: $id)\n";
        } else {
            // Update type if needed
            $stmtUpdate = $pdo->prepare("UPDATE expense_categories SET name = ?, type = ? WHERE id = ?");
            $stmtUpdate->execute([$info['name'], $info['type'], $id]);
            echo "Parent category '{$info['name']}' already exists, updated name/type.\n";
        }
        $parentIds[$slug] = $id;
    }

    // 2. Map existing subcategories to their new parents and rename them
    $existingMappings = [
        'combustible' => [
            'name' => 'Combustible (ACP / Diesel)',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'peajes' => [
            'name' => 'Peajes',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'comida' => [
            'name' => 'Comida',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'hospedaje' => [
            'name' => 'Hospedaje',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'otros' => [
            'name' => 'Otros Costos Operativos de Ruta',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'sueldo' => [
            'name' => 'Salario Básico Conductor',
            'type' => 'fixed',
            'parent_slug' => 'personal_operativo'
        ],
        'auxilio_transporte' => [
            'name' => 'Auxilio Transporte Conductor',
            'type' => 'fixed',
            'parent_slug' => 'personal_operativo'
        ],
        'reparaciones' => [
            'name' => 'Mano de Obra Mecánica / Taller',
            'type' => 'fixed',
            'parent_slug' => 'mantenimiento_activos'
        ],
        'aceite_filtros' => [
            'name' => 'Lubricantes y Aditivos (AdBlue/Urea, Aceites)',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'aire_acondicionado' => [
            'name' => 'Aire Acondicionado',
            'type' => 'fixed',
            'parent_slug' => 'mantenimiento_activos'
        ],
        'mantenimiento' => [
            'name' => 'Otros Gastos Mantenimiento',
            'type' => 'fixed',
            'parent_slug' => 'mantenimiento_activos'
        ]
    ];

    foreach ($existingMappings as $slug => $info) {
        $stmt = $pdo->prepare("SELECT id FROM expense_categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();

        if ($id) {
            $parentId = $parentIds[$info['parent_slug']];
            $stmtUpdate = $pdo->prepare("UPDATE expense_categories SET name = ?, type = ?, parent_id = ? WHERE id = ?");
            $stmtUpdate->execute([$info['name'], $info['type'], $parentId, $id]);
            echo "Updated existing subcategory '{$slug}' -> '{$info['name']}' under parent '{$info['parent_slug']}'\n";
        }
    }

    // 3. Define and insert all new subcategories
    $newSubcategories = [
        // Under Administrativos
        'contador' => [
            'name' => 'Contador',
            'type' => 'fixed',
            'parent_slug' => 'administrativos'
        ],
        'salario_supervisor' => [
            'name' => 'Salario Supervisor',
            'type' => 'fixed',
            'parent_slug' => 'administrativos'
        ],
        'comision_contado' => [
            'name' => 'Comisión Contado',
            'type' => 'fixed',
            'parent_slug' => 'administrativos'
        ],
        'papeleria' => [
            'name' => 'Papelería',
            'type' => 'fixed',
            'parent_slug' => 'administrativos'
        ],
        'gastos_bancarios' => [
            'name' => 'Gastos Bancarios',
            'type' => 'fixed',
            'parent_slug' => 'administrativos'
        ],
        'software' => [
            'name' => 'Software',
            'type' => 'fixed',
            'parent_slug' => 'administrativos'
        ],
        'telefonia' => [
            'name' => 'Telefonía',
            'type' => 'fixed',
            'parent_slug' => 'administrativos'
        ],
        'otros_administrativos' => [
            'name' => 'Otros Administrativos',
            'type' => 'fixed',
            'parent_slug' => 'administrativos'
        ],

        // Under Personal Operativo
        'comision_conductor' => [
            'name' => 'Comisión Conductor',
            'type' => 'fixed',
            'parent_slug' => 'personal_operativo'
        ],
        'aportes_seguridad_social' => [
            'name' => 'Aportes Seguridad Social (Salud, Pensión, ARL)',
            'type' => 'fixed',
            'parent_slug' => 'personal_operativo'
        ],
        'liquidacion_prestaciones' => [
            'name' => 'Liquidación / Prestaciones Sociales (Cesantías, Prima, etc.)',
            'type' => 'fixed',
            'parent_slug' => 'personal_operativo'
        ],
        'viaticos_operativos' => [
            'name' => 'Viáticos Operativos (No reembolsables)',
            'type' => 'fixed',
            'parent_slug' => 'personal_operativo'
        ],
        'otros_gastos_personal_operativo' => [
            'name' => 'Otros Gastos Personal Operativo',
            'type' => 'fixed',
            'parent_slug' => 'personal_operativo'
        ],

        // Under Costos Operativos de Ruta
        'parqueaderos_viaticos' => [
            'name' => 'Parqueaderos y Viáticos (Cruce de ferrys, etc.)',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'lavado_vehiculo_encarparse' => [
            'name' => 'Lavado de Vehículo y Encarparse',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'cargue_descargue' => [
            'name' => 'Cargue y Descargue',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'comision_viaje_terceros' => [
            'name' => 'Comisión de Viaje (Terceros / Intermediarios)',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],
        'ajustes_viaje_faltantes' => [
            'name' => 'Ajustes de Viaje / Faltantes de Carga',
            'type' => 'variable',
            'parent_slug' => 'costos_operativos_ruta'
        ],

        // Under Mantenimiento y Activos
        'repuestos_autopartes' => [
            'name' => 'Repuestos y Autopartes',
            'type' => 'fixed',
            'parent_slug' => 'mantenimiento_activos'
        ],
        'llantas_neumaticos' => [
            'name' => 'Llantas / Neumáticos',
            'type' => 'fixed',
            'parent_slug' => 'mantenimiento_activos'
        ],
        'servicios_terceros' => [
            'name' => 'Servicios de Terceros (Tornos, Soldaduras)',
            'type' => 'fixed',
            'parent_slug' => 'mantenimiento_activos'
        ],
        'seguros' => [
            'name' => 'Seguros (SOAT, Póliza Responsabilidad Civil, Póliza Todo Riesgo)',
            'type' => 'fixed',
            'parent_slug' => 'mantenimiento_activos'
        ],
        'impuestos_tramites' => [
            'name' => 'Impuestos y Trámites (Impuesto vehicular, Revisión Técnico Mecánica, etc.)',
            'type' => 'fixed',
            'parent_slug' => 'mantenimiento_activos'
        ],
        'depreciacion_activos' => [
            'name' => 'Depreciación de Activos (Contable)',
            'type' => 'fixed',
            'parent_slug' => 'mantenimiento_activos'
        ]
    ];

    foreach ($newSubcategories as $slug => $info) {
        $stmt = $pdo->prepare("SELECT id FROM expense_categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            $parentId = $parentIds[$info['parent_slug']];
            $stmtInsert = $pdo->prepare("INSERT INTO expense_categories (name, slug, type, parent_id) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$info['name'], $slug, $info['type'], $parentId]);
            echo "Created subcategory '{$info['name']}' under parent '{$info['parent_slug']}'\n";
        } else {
            // Already exists, just make sure parent is correct
            $parentId = $parentIds[$info['parent_slug']];
            $stmtUpdate = $pdo->prepare("UPDATE expense_categories SET name = ?, type = ?, parent_id = ? WHERE id = ?");
            $stmtUpdate->execute([$info['name'], $info['type'], $parentId, $id]);
            echo "Subcategory '{$info['name']}' already exists, updated details.\n";
        }
    }

    // 4. Clean up old parent categories (operativos_viaje, fijos_mantenimiento, administrativo)
    $oldSlugs = ['operativos_viaje', 'fijos_mantenimiento', 'administrativo'];
    foreach ($oldSlugs as $slug) {
        $stmt = $pdo->prepare("SELECT id FROM expense_categories WHERE slug = ? AND parent_id IS NULL");
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();

        if ($id) {
            // Check if there are still any children pointing to this parent
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM expense_categories WHERE parent_id = ?");
            $stmtCount->execute([$id]);
            $count = $stmtCount->fetchColumn();

            if ($count == 0) {
                // Safe to delete
                $stmtDel = $pdo->prepare("DELETE FROM expense_categories WHERE id = ?");
                $stmtDel->execute([$id]);
                echo "Deleted obsolete parent category '{$slug}' (ID: $id)\n";
            } else {
                echo "Warning: Parent category '{$slug}' (ID: $id) still has $count children, cannot delete.\n";
            }
        }
    }

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    throw $e;
}
