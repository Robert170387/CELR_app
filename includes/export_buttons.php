<?php
/**
 * Botones de exportación reutilizables
 * Usar: include_once 'includes/export_buttons.php'; exportButtons('viajes', $_GET);
 */
function exportButtons(string $modulo, array $filtros = []): void {
    $q = array_merge($filtros, ['modulo' => $modulo]);
    $qs_pdf = http_build_query($q);
    $qs_xl  = http_build_query(array_merge($q, ['formato' => 'xlsx']));
    $qs_csv = http_build_query(array_merge($q, ['formato' => 'csv']));
    echo <<<HTML
    <div class="export-buttons flex items-center gap-2 flex-wrap">
      <span class="text-xs text-gray-400 mr-1">Exportar:</span>
      <a href="export_pdf.php?{$qs_pdf}"
         target="_blank"
         class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg
                bg-gradient-to-r from-red-500 to-rose-600 text-white shadow hover:shadow-md hover:-translate-y-px transition-all">
        🖨 PDF / Imprimir
      </a>
      <a href="export.php?{$qs_xl}"
         class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg
                bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow hover:shadow-md hover:-translate-y-px transition-all">
        ⬇ Excel
      </a>
      <a href="export.php?{$qs_csv}"
         class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg
                bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow hover:shadow-md hover:-translate-y-px transition-all">
        ⬇ CSV
      </a>
    </div>
    HTML;
}
