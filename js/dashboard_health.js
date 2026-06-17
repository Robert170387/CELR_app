document.addEventListener('DOMContentLoaded', function () {
    const startMigrationCheck = () => {
        const statusContainer = document.getElementById('migration-status-container');
        if (!statusContainer) return;

        statusContainer.innerHTML = '<span class="text-xs text-slate-400 animate-pulse">Verificando estado...</span>';

        fetch('api_migration_status.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    statusContainer.innerHTML = `<span class="text-xs text-red-500 font-bold">Error: ${data.error}</span>`;
                    return;
                }

                let html = '';
                if (data.status === 'success') {
                    html = `
                        <div class="flex items-center space-x-2">
                             <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                             <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Base de Datos al día</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1 pl-6">
                            Migraciones: ${data.applied_migrations} / ${data.total_files}
                        </div>
                    `;
                } else {
                    html = `
                        <div class="flex flex-col space-y-2">
                            <div class="flex items-center space-x-2 animate-pulse">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">${data.pending_count} Migraciones Pendientes</span>
                            </div>
                            <button onclick="window.location.href='migrate.php'" class="ml-6 px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-bold rounded shadow-sm transition-colors w-fit">
                                Ejecutar Ahora &rarr;
                            </button>
                        </div>
                    `;
                }

                // Append DB connection warning if needed
                if (data.db_connection !== 'ok') {
                    html += `<div class="mt-2 text-[10px] font-bold text-red-600">❌ Error de Conexión a BD</div>`;
                }

                if (data.missing_tables && data.missing_tables.length > 0) {
                    html += `<div class="mt-1 text-[10px] font-bold text-red-600">Faltan tablas: ${data.missing_tables.join(', ')}</div>`;
                }

                statusContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error fetching migration status:', error);
                statusContainer.innerHTML = '<span class="text-xs text-red-400">Error de conexión</span>';
            });
    };

    const startAlertsCheck = () => {
        const alertsContainer = document.getElementById('system-alerts-container');
        const alertsList = document.getElementById('alerts-list');
        if (!alertsContainer || !alertsList) return;

        fetch('api.php?action=getSystemAlerts')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.alerts.length > 0) {
                    alertsContainer.style.display = 'block';
                    alertsList.innerHTML = data.alerts.map(alert => {
                        let icon = '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                        let bg = 'bg-red-50 border-red-100';

                        if (alert.type === 'status_change') {
                            icon = '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>';
                            bg = 'bg-blue-50 border-blue-100';
                        }

                        return `
                            <div class="flex items-start space-x-4 p-4 rounded-xl border-2 ${bg} shadow-sm transition-all hover:scale-[1.02]">
                                <div class="p-2 bg-white rounded-lg shadow-sm border">
                                    ${icon}
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-black text-slate-900 leading-tight">${alert.title}</h4>
                                    <p class="text-[11px] text-slate-500 font-medium mt-1">${alert.message}</p>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">${alert.time_ago}</span>
                                        <span class="text-[10px] font-black text-slate-600 uppercase">#${alert.entity_id || ''}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            })
            .catch(error => console.error('Error fetching alerts:', error));
    };

    const initBackupButton = () => {
        const btn = document.getElementById('btn-generate-backup');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (!confirm('¿Está seguro de generar un respaldo de la base de datos ahora?')) return;

            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="w-3 h-3 mr-1.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> GENERANDO...';

            fetch('api.php?action=triggerDatabaseBackup')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Respaldo generado con éxito: ' + data.filename);
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error generating backup:', error);
                    alert('Error técnico al generar respaldo.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });
    };

    startMigrationCheck();
    startAlertsCheck();
    initBackupButton();
});
