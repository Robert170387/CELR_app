<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CELR-App Test Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd',
                            400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .test-card { transition: all 0.3s ease; }
        .test-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .status-pass { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .status-fail { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .status-pending { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .progress-ring { transform: rotate(-90deg); }
        @keyframes pulse-green {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            50% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        }
        .pulse-success { animation: pulse-green 2s infinite; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-900">CELR-App Test Dashboard</h1>
                    <p class="mt-2 text-slate-600">Batería de Pruebas Integrales para Validación de Sistema</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span id="lastRun" class="text-sm text-slate-500">Última ejecución: -</span>
                    <button onclick="runTests()" id="runBtn" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-brand-500/30 transition-all hover:scale-105 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Ejecutar Pruebas
                    </button>
                </div>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Overall Progress -->
            <div class="test-card bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Progreso Total</p>
                        <p class="mt-2 text-4xl font-black text-slate-900" id="progressPercent">0%</p>
                    </div>
                    <div class="relative w-20 h-20">
                        <svg class="progress-ring w-20 h-20" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                            <circle id="progressCircle" cx="50" cy="50" r="45" fill="none" stroke="#3b82f6" stroke-width="8"
                                stroke-dasharray="283" stroke-dashoffset="283" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-lg font-bold text-brand-600" id="progressValue">0/0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Passed Tests -->
            <div class="test-card bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 status-pass rounded-xl flex items-center justify-center text-white mr-4 pulse-success">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Aprobadas</p>
                        <p class="text-3xl font-black text-emerald-600" id="passedCount">0</p>
                    </div>
                </div>
            </div>

            <!-- Failed Tests -->
            <div class="test-card bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 status-fail rounded-xl flex items-center justify-center text-white mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Fallidas</p>
                        <p class="text-3xl font-black text-red-600" id="failedCount">0</p>
                    </div>
                </div>
            </div>

            <!-- Duration -->
            <div class="test-card bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-slate-800 rounded-xl flex items-center justify-center text-white mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Duración</p>
                        <p class="text-3xl font-black text-slate-800" id="duration">0.00s</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Categories -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Database Health -->
            <div class="test-card bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center">
                        <span class="w-2 h-6 bg-blue-500 rounded-full mr-3"></span>
                        1. Salud de Base de Datos
                    </h3>
                    <span id="dbStatus" class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">PENDIENTE</span>
                </div>
                <div id="dbResults" class="space-y-2">
                    <div class="animate-pulse flex space-x-4">
                        <div class="flex-1 space-y-2 py-1">
                            <div class="h-2 bg-slate-200 rounded"></div>
                            <div class="h-2 bg-slate-200 rounded"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MVC Flow -->
            <div class="test-card bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center">
                        <span class="w-2 h-6 bg-purple-500 rounded-full mr-3"></span>
                        2. Flujo MVC
                    </h3>
                    <span id="mvcStatus" class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">PENDIENTE</span>
                </div>
                <div id="mvcResults" class="space-y-2">
                    <div class="animate-pulse flex space-x-4">
                        <div class="flex-1 space-y-2 py-1">
                            <div class="h-2 bg-slate-200 rounded"></div>
                            <div class="h-2 bg-slate-200 rounded"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="test-card bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center">
                        <span class="w-2 h-6 bg-amber-500 rounded-full mr-3"></span>
                        3. Trazabilidad y Auditoría
                    </h3>
                    <span id="auditStatus" class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">PENDIENTE</span>
                </div>
                <div id="auditResults" class="space-y-2">
                    <div class="animate-pulse flex space-x-4">
                        <div class="flex-1 space-y-2 py-1">
                            <div class="h-2 bg-slate-200 rounded"></div>
                            <div class="h-2 bg-slate-200 rounded"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance -->
            <div class="test-card bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center">
                        <span class="w-2 h-6 bg-emerald-500 rounded-full mr-3"></span>
                        4. Rendimiento de Ubicaciones
                    </h3>
                    <span id="perfStatus" class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">PENDIENTE</span>
                </div>
                <div id="perfResults" class="space-y-2">
                    <div class="animate-pulse flex space-x-4">
                        <div class="flex-1 space-y-2 py-1">
                            <div class="h-2 bg-slate-200 rounded"></div>
                            <div class="h-2 bg-slate-200 rounded"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Test Runs -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Ejecuciones Recientes</h3>
            <div id="recentRuns" class="space-y-2">
                <p class="text-slate-500 text-center py-4">No hay ejecuciones recientes</p>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 shadow-2xl text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-brand-600 mx-auto mb-4"></div>
            <p class="text-lg font-bold text-slate-900">Ejecutando pruebas...</p>
            <p class="text-sm text-slate-500 mt-2">Esto puede tomar unos segundos</p>
        </div>
    </div>

    <script>
        let testResults = {
            total: 0,
            passed: 0,
            failed: 0,
            duration: 0,
            categories: {
                db: { passed: 0, failed: 0, tests: [] },
                mvc: { passed: 0, failed: 0, tests: [] },
                audit: { passed: 0, failed: 0, tests: [] },
                perf: { passed: 0, failed: 0, tests: [] }
            }
        };

        async function runTests() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
            document.getElementById('loadingOverlay').classList.add('flex');
            document.getElementById('runBtn').disabled = true;

            try {
                const response = await fetch('test_api.php?action=run');
                const data = await response.json();
                
                testResults = data;
                updateDashboard();
                
                document.getElementById('lastRun').textContent = 'Última ejecución: ' + new Date().toLocaleString();
                
                // Guardar en localStorage para historial
                saveToHistory(data);
            } catch (error) {
                console.error('Error:', error);
                alert('Error al ejecutar pruebas: ' + error.message);
            } finally {
                document.getElementById('loadingOverlay').classList.add('hidden');
                document.getElementById('loadingOverlay').classList.remove('flex');
                document.getElementById('runBtn').disabled = false;
            }
        }

        function updateDashboard() {
            // Update counters
            document.getElementById('passedCount').textContent = testResults.passed;
            document.getElementById('failedCount').textContent = testResults.failed;
            document.getElementById('progressValue').textContent = `${testResults.passed}/${testResults.total}`;
            
            const percent = testResults.total > 0 ? Math.round((testResults.passed / testResults.total) * 100) : 0;
            document.getElementById('progressPercent').textContent = percent + '%';
            document.getElementById('duration').textContent = testResults.duration.toFixed(2) + 's';
            
            // Update progress circle
            const circle = document.getElementById('progressCircle');
            const circumference = 283;
            const offset = circumference - (percent / 100) * circumference;
            circle.style.strokeDashoffset = offset;
            
            // Update category colors
            circle.style.stroke = percent >= 80 ? '#10b981' : percent >= 50 ? '#f59e0b' : '#ef4444';
            document.getElementById('progressPercent').className = `mt-2 text-4xl font-black ${percent >= 80 ? 'text-emerald-600' : percent >= 50 ? 'text-amber-600' : 'text-red-600'}`;

            // Update category results
            updateCategory('db', testResults.categories.db);
            updateCategory('mvc', testResults.categories.mvc);
            updateCategory('audit', testResults.categories.audit);
            updateCategory('perf', testResults.categories.perf);
        }

        function updateCategory(category, data) {
            const statusEl = document.getElementById(category + 'Status');
            const resultsEl = document.getElementById(category + 'Results');
            
            if (data.tests.length === 0) {
                resultsEl.innerHTML = '<p class="text-slate-400 text-center py-2">Sin resultados</p>';
                return;
            }
            
            const total = data.passed + data.failed;
            const allPass = data.failed === 0 && total > 0;
            
            statusEl.textContent = allPass ? 'PASS' : data.failed > 0 ? 'FAIL' : 'PENDIENTE';
            statusEl.className = `px-3 py-1 rounded-full text-xs font-bold ${allPass ? 'bg-emerald-100 text-emerald-700' : data.failed > 0 ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'}`;
            
            let html = '';
            data.tests.forEach(test => {
                const icon = test.pass ? '✓' : '✗';
                const color = test.pass ? 'text-emerald-600' : 'text-red-600';
                const bg = test.pass ? 'bg-emerald-50' : 'bg-red-50';
                
                html += `
                    <div class="flex items-center justify-between p-3 ${bg} rounded-lg">
                        <div class="flex items-center">
                            <span class="${color} font-bold mr-2">${icon}</span>
                            <span class="text-sm text-slate-700">${test.name}</span>
                        </div>
                        ${test.details ? `<span class="text-xs text-slate-500">${test.details}</span>` : ''}
                    </div>
                `;
            });
            
            resultsEl.innerHTML = html;
        }

        function saveToHistory(data) {
            let history = JSON.parse(localStorage.getItem('testHistory') || '[]');
            history.unshift({
                date: new Date().toISOString(),
                passed: data.passed,
                failed: data.failed,
                total: data.total,
                duration: data.duration
            });
            
            // Keep only last 10
            history = history.slice(0, 10);
            localStorage.setItem('testHistory', JSON.stringify(history));
            
            updateHistoryDisplay();
        }

        function updateHistoryDisplay() {
            const history = JSON.parse(localStorage.getItem('testHistory') || '[]');
            const container = document.getElementById('recentRuns');
            
            if (history.length === 0) {
                container.innerHTML = '<p class="text-slate-500 text-center py-4">No hay ejecuciones recientes</p>';
                return;
            }
            
            let html = '';
            history.forEach((run, index) => {
                const percent = Math.round((run.passed / run.total) * 100);
                const color = percent >= 80 ? 'text-emerald-600' : percent >= 50 ? 'text-amber-600' : 'text-red-600';
                const date = new Date(run.date).toLocaleString();
                
                html += `
                    <div class="flex items-center justify-between p-3 ${index % 2 === 0 ? 'bg-slate-50' : 'bg-white'} rounded-lg">
                        <div class="flex items-center">
                            <span class="font-bold ${color} mr-3">${percent}%</span>
                            <span class="text-sm text-slate-600">${run.passed}/${run.total} aprobadas</span>
                        </div>
                        <span class="text-xs text-slate-400">${date}</span>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Load history on page load
        updateHistoryDisplay();
    </script>
</body>
</html>
