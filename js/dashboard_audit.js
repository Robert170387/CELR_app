document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('audit-activity-container');
    if (!container) return; // Not admin or widget not present

    container.innerHTML = '<div class="text-xs text-slate-400 animate-pulse p-4">Cargando actividad...</div>';

    fetch('api_audit_logs.php')
        .then(response => {
            if (response.status === 403) throw new Error('No autorizado');
            return response.json();
        })
        .then(data => {
            if (data.error) {
                container.innerHTML = `<div class="p-4 text-xs text-red-500 font-bold">Error: ${data.error}</div>`;
                return;
            }

            if (!data.logs || data.logs.length === 0) {
                container.innerHTML = `
                    <div class="p-6 text-center text-slate-400">
                        <p class="text-xs">No hay actividad crítica reciente.</p>
                    </div>`;
                return;
            }

            let html = '<div class="divide-y divide-slate-100">';
            data.logs.forEach(log => {
                const actionColor = log.action === 'DELETE' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600';

                html += `
                    <div class="p-3 hover:bg-slate-50 transition-colors">
                        <div class="flex justify-between items-start">
                            <div class="flex items-center space-x-2">
                                <span class="capitalize text-[10px] font-bold ${actionColor} px-1.5 py-0.5 rounded">
                                    ${log.action}
                                </span>
                                <span class="text-[11px] font-bold text-slate-700">
                                    ${log.entity_type}
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-400 whitespace-nowrap">Hace ${log.time_ago}</span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 leading-snug">
                            <span class="font-bold text-slate-800">${log.actor}</span>: ${log.details}
                        </p>
                    </div>
                `;
            });
            html += '</div>';

            // Footer link
            html += `
                <div class="p-2 border-t border-slate-100 bg-slate-50 rounded-b-xl text-center">
                    <a href="audit_report.php" class="text-[10px] font-bold text-brand-600 hover:text-brand-700 uppercase tracking-wider">
                        Ver Reporte Completo &rarr;
                    </a>
                </div>
            `;

            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Audit Load Error:', error);
            container.innerHTML = '<div class="p-4 text-xs text-red-400">No se pudo cargar la actividad.</div>';
        });
});
