<?php
include 'includes/db.php';
include 'includes/header.php';

// Fetch clients for global selection
$clients = $pdo->query("SELECT id, business_name, firstname, lastname1, person_type FROM clients ORDER BY business_name ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Importación Histórica Interactiva</h2>
        <p class="text-slate-500 mt-2">Copia tus datos desde Excel y pégalos aquí para procesar miles de viajes en
            segundos.</p>
    </div>

    <!-- Step 1: Paste Area -->
    <div id="step-1"
        class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-8 transition-all">
        <div class="p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-brand-100 rounded-2xl flex items-center justify-center text-brand-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Paso 1: Copiar y Pegar</h3>
                        <p class="text-sm text-slate-500">Haz clic en el área inferior y presiona Ctrl + V</p>
                    </div>
                </div>
            </div>

            <textarea id="paste-area"
                class="w-full h-48 p-4 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl outline-none focus:border-brand-500 transition-colors font-mono text-sm placeholder:text-slate-300"
                placeholder="Pega aquí los datos de Excel... (incluyendo encabezados si los tienes)"></textarea>

            <div class="mt-6 flex justify-end">
                <button onclick="parsePasteContent()"
                    class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-2xl transition-all h-fit flex items-center">
                    Procesar Datos &rarr;
                </button>
            </div>
        </div>
    </div>

    <!-- Step 2: Grid and Mapping -->
    <div id="step-2"
        class="hidden bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden mb-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <div class="p-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Paso 2: Mapeo de Columnas</h3>
                        <p class="text-sm text-slate-500">Identifica qué columna corresponde a cada dato.</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Asignar a
                        Cliente:</label>
                    <select id="global-client"
                        class="bg-transparent border-none outline-none text-sm font-bold text-slate-700">
                        <option value="">-- Seleccione Cliente --</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($c['person_type'] == 'Jurídica' ? $c['business_name'] : $c['firstname'] . ' ' . $c['lastname1']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-100 mb-6">
                <table class="w-full text-left text-sm" id="import-grid">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr id="grid-header-mapping">
                            <!-- JS populated -->
                        </tr>
                    </thead>
                    <tbody id="grid-body" class="divide-y divide-slate-50">
                        <!-- JS populated -->
                    </tbody>
                </table>
            </div>

            <div class="flex justify-between items-center">
                <button onclick="resetImport()"
                    class="text-slate-400 hover:text-slate-600 font-bold text-sm">Reiniciar</button>
                <div class="flex items-center space-x-4">
                    <div id="processed-stats" class="text-sm font-bold text-slate-500 hidden mr-4"></div>
                    <button id="btn-submit-import" onclick="submitImport()"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-10 rounded-2xl transition-all shadow-lg shadow-green-200">
                        Completar Importación
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Summary Container -->
    <div id="error-summary"
        class="hidden animate-in fade-in duration-300 p-6 bg-red-50 border border-red-200 rounded-3xl mb-8">
        <h4 class="text-red-800 font-bold mb-2">Se encontraron errores en la importación:</h4>
        <ul id="error-list" class="list-disc list-inside text-red-700 text-sm space-y-1"></ul>
    </div>
</div>

<script>
    let parsedData = [];
    const columnOptions = [
        { value: 'ignore', label: '--- Ignorar ---' },
        { value: 'fecha', label: '📅 Fecha (D/M/A)' },
        { value: 'placa', label: '🚛 Placa' },
        { value: 'manifiesto', label: '📄 Manifiesto' },
        { value: 'origen', label: '📍 Origen' },
        { value: 'destino', label: '🏁 Destino' },
        { value: 'flete_bruto', label: '💰 Flete Bruto' },
        { value: 'flete_neto', label: '💵 Flete Neto' }
    ];

    function parsePasteContent() {
        const text = document.getElementById('paste-area').value.trim();
        if (!text) {
            alert('Por favor pega algunos datos primero.');
            return;
        }

        const rows = text.split('\n');
        parsedData = rows.map(row => row.split('\t').map(cell => cell.trim()));

        if (parsedData.length === 0) return;

        renderGrid();
        document.getElementById('step-1').classList.add('opacity-50', 'pointer-events-none');
        document.getElementById('step-2').classList.remove('hidden');
    }

    function renderGrid() {
        const headerRow = document.getElementById('grid-header-mapping');
        const body = document.getElementById('grid-body');

        headerRow.innerHTML = '';
        body.innerHTML = '';

        const numCols = parsedData[0].length;

        // Render Mapping Selects
        for (let c = 0; c < numCols; c++) {
            const th = document.createElement('th');
            th.className = 'px-4 py-4';

            const select = document.createElement('select');
            select.className = 'w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-[11px] font-bold text-slate-600 outline-none focus:border-brand-500';
            select.dataset.colIndex = c;

            columnOptions.forEach(opt => {
                const o = document.createElement('option');
                o.value = opt.value;
                o.textContent = opt.label;
                // Basic auto-mapping heuristics
                const firstCell = (parsedData[0][c] || '').toLowerCase();
                if (c === 0 && firstCell.includes('fecha')) o.selected = (opt.value === 'fecha');
                if (firstCell.includes('placa')) o.selected = (opt.value === 'placa');
                if (firstCell.includes('manifiesto')) o.selected = (opt.value === 'manifiesto');
                if (firstCell.includes('origen')) o.selected = (opt.value === 'origen');
                if (firstCell.includes('destino')) o.selected = (opt.value === 'destino');
                if (firstCell.includes('bruto')) o.selected = (opt.value === 'flete_bruto');
                if (firstCell.includes('neto')) o.selected = (opt.value === 'flete_neto');

                select.appendChild(o);
            });

            th.appendChild(select);
            headerRow.appendChild(th);
        }

        // Render Sample Rows (max 50 for preview)
        const previewRows = parsedData.slice(0, 50);
        previewRows.forEach((row, i) => {
            const tr = document.createElement('tr');
            row.forEach(cell => {
                const td = document.createElement('td');
                td.className = 'px-4 py-3 text-slate-600 whitespace-nowrap overflow-hidden text-ellipsis max-w-[200px]';
                td.textContent = cell;
                tr.appendChild(td);
            });
            body.appendChild(tr);
        });

        document.getElementById('processed-stats').innerText = `${parsedData.length} filas detectadas.`;
        document.getElementById('processed-stats').classList.remove('hidden');
    }

    function resetImport() {
        location.reload();
    }

    async function submitImport() {
        const btn = document.getElementById('btn-submit-import');
        const mapping = {};
        const selects = document.querySelectorAll('#grid-header-mapping select');

        let hasMapping = false;
        selects.forEach(s => {
            if (s.value !== 'ignore') {
                mapping[s.value] = parseInt(s.dataset.colIndex);
                hasMapping = true;
            }
        });

        if (!hasMapping) {
            alert('Debes mapear al menos una columna.');
            return;
        }

        const clientId = document.getElementById('global-client').value;
        if (!clientId) {
            if (!confirm('No has seleccionado un cliente. ¿Continuar de todos modos?')) return;
        }

        // Format data for backend
        const dataToSubmit = parsedData.map(row => {
            const obj = {};
            for (const key in mapping) {
                obj[key] = row[mapping[key]];
            }
            obj.client_id = clientId;
            return obj;
        });

        // Remove first row if it was headers (heuristic: if it contains text in a money field)
        const firstRow = dataToSubmit[0];
        if (isNaN(parseFloat((firstRow.flete_bruto || "0").replace(/[^\d.]/g, '')))) {
            dataToSubmit.shift();
        }

        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Procesando...`;

        try {
            const response = await fetch('save_bulk_trips.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ data: dataToSubmit })
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                location.href = 'trips.php?status=imported';
            } else {
                document.getElementById('error-summary').classList.remove('hidden');
                const list = document.getElementById('error-list');
                list.innerHTML = '';
                (result.errors || []).forEach(err => {
                    const li = document.createElement('li');
                    li.textContent = err;
                    list.appendChild(li);
                });
                alert('Se encontraron errores en la importación. Revisa el resumen al final de la página.');
            }
        } catch (e) {
            console.error(e);
            alert('Error fatal al enviar datos al servidor.');
        } finally {
            btn.disabled = false;
            btn.innerText = 'Completar Importación';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>