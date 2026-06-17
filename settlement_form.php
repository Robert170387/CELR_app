<?php
include 'includes/db.php';
include 'includes/header.php';

$drivers = $pdo->query("SELECT id, firstname, lastname FROM personnel WHERE type='Conductor' AND active=1 ORDER BY firstname ASC")->fetchAll();

// Fetch company config for logo
$stmtC = $pdo->query("SELECT logo_path FROM config LIMIT 1");
$globalConfig = $stmtC->fetch();
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="settlementModule()">
    <div class="md:grid md:grid-cols-4 md:gap-6">
        <!-- Controls Sidebar -->
        <div class="md:col-span-1 space-y-6">
            <div class="bg-white p-6 shadow rounded-lg border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Parámetros</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Conductor</label>
                        <select x-model="filters.driver_id" @change="fetchData()"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                            <option value="">Seleccione...</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?php echo $d['id']; ?>">
                                    <?php echo htmlspecialchars($d['firstname'] . ' ' . $d['lastname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha Inicial</label>
                        <input type="date" x-model="filters.date_start" @change="fetchData()"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha Final</label>
                        <input type="date" x-model="filters.date_end" @change="fetchData()"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                    </div>

                    <div x-show="dataLoaded">
                        <label class="block text-sm font-medium text-gray-700 uppercase mb-1 mt-4">Observaciones</label>
                        <textarea x-model="form.notes" rows="3"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm"
                            placeholder="Comentarios adicionales..."></textarea>
                    </div>

                    <div x-show="dataLoaded" class="pt-4 border-t border-gray-100 mt-4">
                        <label class="block text-sm font-bold text-brand-700 uppercase mb-1">Abono Propietario (Fin
                            Mes)</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" x-model.number="form.partial_payment"
                                class="focus:ring-brand-500 focus:border-brand-500 block w-full pl-7 pr-12 sm:text-sm border-brand-300 rounded-md font-bold text-brand-900"
                                placeholder="0">
                        </div>
                        <p class="mt-1 text-[10px] text-gray-400 font-medium">Este valor se restará del Neto Final.</p>
                    </div>

                    <div class="pt-4">
                        <button @click="saveSettlement()" :disabled="!dataLoaded || isSaving"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50">
                            <span x-show="!isSaving">Guardar Liquidación</span>
                            <span x-show="isSaving">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="bg-brand-900 p-6 shadow rounded-lg text-white" x-show="dataLoaded">
                <h4 class="text-sm font-bold uppercase tracking-wider opacity-70">Saldo Final a Pagar</h4>
                <div class="text-3xl font-black mt-1" x-text="formatCurrency(calculatedNetPay)"></div>
                <div class="mt-4 space-y-2 text-xs opacity-90">
                    <div class="flex justify-between">
                        <span>Neto antes de Abono:</span>
                        <span x-text="formatCurrency(summary.net_to_pay)"></span>
                    </div>
                    <div class="flex justify-between border-t border-white/20 pt-1 text-yellow-300 font-bold">
                        <span>(-) Abono Propietario:</span>
                        <span x-text="formatCurrency(form.partial_payment)"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pay Stub Preview -->
        <div class="md:col-span-3">
            <div x-show="!dataLoaded"
                class="flex flex-col items-center justify-center h-64 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p>Seleccione un conductor y rango de fechas para generar la liquidación</p>
            </div>

            <div x-show="dataLoaded" id="letter-container" class="print:m-0 print:p-0">
                <div class="bg-white shadow-2xl rounded-lg overflow-hidden border border-gray-200 print:shadow-none print:border-0 h-full flex flex-col"
                    id="paymentStub">
                    <!-- Receipt Header -->
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex justify-between items-center">
                            <!-- Left: Logo and Driver Info Group -->
                            <div class="flex items-center space-x-6">
                                <?php if (!empty($globalConfig['logo_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($globalConfig['logo_path']); ?>" alt="Logo"
                                        class="h-14 w-auto">
                                <?php else: ?>
                                    <div
                                        class="h-14 w-14 bg-brand-100 flex items-center justify-center rounded text-brand-700 font-bold text-xs text-center">
                                        LOGO<br>EMPRESA</div>
                                <?php endif; ?>

                                <!-- Driver Info Grid -->
                                <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-[10px]">
                                    <div class="space-y-0.5">
                                        <p><span class="font-black text-gray-900 uppercase">Conductor:</span> <span
                                                class="text-gray-900 font-bold ml-1 text-sm whitespace-nowrap"
                                                x-text="personnel.firstname + ' ' + personnel.lastname"></span></p>
                                        <p><span class="font-black text-gray-900 uppercase">Cédula:</span> <span
                                                class="text-gray-900 ml-1" x-text="personnel.document_number"></span>
                                        </p>
                                        <p><span class="font-black text-gray-900 uppercase">Cuenta:</span> <span
                                                class="text-gray-900 ml-1"
                                                x-text="personnel.bank_account || 'N/A'"></span></p>
                                    </div>
                                    <div class="space-y-0.5">
                                        <p><span class="font-black text-gray-900 uppercase">Fecha Doc:</span> <span
                                                class="text-gray-900 ml-1"><?php echo date('d/m/Y'); ?></span></p>
                                        <p><span class="font-black text-gray-900 uppercase">Periodo:</span> <span
                                                class="text-gray-900 ml-1 font-bold whitespace-nowrap"
                                                x-text="formatDate(filters.date_start) + ' - ' + formatDate(filters.date_end)"></span>
                                        </p>
                                        <p class="text-[9px] text-gray-400 italic font-medium mt-1">Soporte interno de
                                            liquidación.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Title and Receipt No -->
                            <div class="text-right">
                                <h1 class="text-lg font-black text-gray-900 uppercase leading-none">Desprendible de Pago
                                </h1>
                                <p class="text-[10px] font-bold text-gray-500 mb-2">COMISIONES POR VIAJE</p>
                                <div
                                    class="text-xs font-mono border-2 border-red-600 inline-block px-3 py-1 text-red-600 font-bold rounded bg-red-50">
                                    RECIBO NO: <span class="text-sm font-black">PROV-<?php echo date('Ymd'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-[10px]">
                            <thead class="bg-gray-900 text-white uppercase font-bold">
                                <tr>
                                    <th class="px-2 py-2 text-left w-6">#</th>
                                    <th class="px-2 py-2 text-left">ODT</th>
                                    <th class="px-2 py-2 text-left">F. Manif.</th>
                                    <th class="px-2 py-2 text-left">Vehículo</th>
                                    <th class="px-2 py-2 text-left">Origen - Destino</th>
                                    <th class="px-2 py-2 text-left">Empresa / N° Manifiesto</th>
                                    <th class="px-2 py-2 text-left">Tipo</th>
                                    <th class="px-2 py-2 text-right">Flete Neto</th>
                                    <th class="px-2 py-2 text-right">Ant. Manif.</th>
                                    <th class="px-2 py-2 text-right">Ant. Prop.</th>
                                    <th class="px-2 py-2 text-right">Gastos</th>
                                    <th class="px-2 py-2 text-right">Comisión</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(trip, index) in trips" :key="trip.id">
                                    <tr class="hover:bg-gray-50 border-b border-gray-100">
                                        <td class="px-2 py-2 text-gray-400 font-bold border-r border-gray-50"
                                            x-text="index + 1"></td>
                                        <td class="px-2 py-2 font-bold text-gray-900" x-text="'ODT-' + trip.id"></td>
                                        <td class="px-2 py-2 whitespace-nowrap text-slate-950 font-medium"
                                            x-text="formatDate(trip.manifest_date || trip.date_load)"></td>
                                        <td class="px-2 py-2 font-bold text-gray-700" x-text="trip.vehicle"></td>
                                        <td class="px-2 py-2 text-slate-950 font-medium truncate max-w-[120px]"
                                            x-text="trip.origin + ' - ' + trip.destination"></td>
                                        <td class="px-2 py-2 text-slate-950 truncate max-w-[150px]">
                                            <div class="font-bold" x-text="trip.company"></div>
                                            <div x-text="trip.manifest"></div>
                                        </td>
                                        <td class="px-2 py-2 capitalize font-bold text-gray-600" x-text="trip.type">
                                        </td>
                                        <td class="px-2 py-2 text-right font-medium text-gray-900"
                                            x-text="formatCurrency(trip.flete_neto)"></td>
                                        <td class="px-2 py-2 text-right text-slate-950 font-medium"
                                            x-text="formatCurrency(trip.advance_manifest)"></td>
                                        <td class="px-2 py-2 text-right text-slate-950 font-medium"
                                            x-text="formatCurrency(trip.advance_owner)"></td>
                                        <td class="px-2 py-2 text-right text-red-600 font-medium"
                                            x-text="formatCurrency(trip.expenses)"></td>
                                        <td class="px-2 py-2 text-right font-bold text-brand-700"
                                            x-text="formatCurrency(trip.commission)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Summary -->
                    <div class="p-4 bg-white grid grid-cols-2 gap-8 border-t border-gray-100">
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <h4
                                    class="text-xs font-black text-slate-950 uppercase mb-2 border-b border-slate-200 pb-1">
                                    Conteo de Operación</h4>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-slate-950 font-medium">Viajes Nacionales:</span>
                                    <span class="font-black text-gray-900" x-text="summary.count_national"></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-950 font-medium">Viajes Urbanos:</span>
                                    <span class="font-black text-gray-900" x-text="summary.count_urban"></span>
                                </div>
                            </div>

                            <div class="pt-1">
                                <div
                                    class="flex justify-between items-center bg-brand-50 p-3 rounded-lg border border-brand-200">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-brand-700 uppercase">Total a Recibir:</span>
                                        <span class="text-[10px] text-brand-500 italic font-medium mt-[-2px]">Neto
                                            Final</span>
                                    </div>
                                    <span class="text-2xl font-black text-brand-800"
                                        x-text="formatCurrency(calculatedNetPay)"></span>
                                </div>
                            </div>

                            <div class="mt-2 border-t border-gray-100 pt-2 flex flex-col space-y-1">
                                <div class="flex justify-between text-sm font-bold text-gray-700">
                                    <span>NETO DE LIQUIDACIÓN:</span>
                                    <span x-text="formatCurrency(summary.net_to_pay)"></span>
                                </div>
                                <div
                                    class="flex justify-between text-xs text-brand-600 font-black italic bg-brand-50 px-3 py-1 rounded">
                                    <span>(-) ABONO PROPIETARIO FIN MES:</span>
                                    <span x-text="formatCurrency(form.partial_payment)"></span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-0.5 text-xs">
                            <div class="flex justify-between py-0.5 border-b border-gray-50">
                                <span class="font-bold text-gray-900 uppercase">Comisiones:</span>
                                <span class="font-black text-gray-900"
                                    x-text="formatCurrency(summary.total_commissions)"></span>
                            </div>
                            <div class="flex justify-between py-0.5 border-b border-gray-50">
                                <span class="font-bold text-gray-900 uppercase">Sueldo Básico:</span>
                                <span class="font-black text-gray-900"
                                    x-text="formatCurrency(personnel.salary_basic)"></span>
                            </div>
                            <div class="flex justify-between py-0.5 border-b border-gray-50">
                                <span class="font-bold text-gray-900 uppercase">Auxilio Transp:</span>
                                <span class="font-black text-gray-900"
                                    x-text="formatCurrency(summary.transport_assistance_paid)"></span>
                            </div>
                            <div
                                class="flex justify-between py-1.5 text-brand-700 font-black text-sm uppercase border-t border-brand-100">
                                <span>Devengado Subtotal:</span>
                                <span x-text="formatCurrency(summary.gross_total)"></span>
                            </div>

                            <div class="pt-2 mt-2 border-t-2 border-brand-900 space-y-1">
                                <h4 class="text-xs font-black text-brand-900 uppercase mb-2">Anticipos y Gastos</h4>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 uppercase">Ant. Manifiesto:</span>
                                    <span class="font-bold text-gray-900"
                                        x-text="formatCurrency(summary.total_advances_manifest)"></span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 uppercase">Ant. Propietario:</span>
                                    <span class="font-bold text-gray-900"
                                        x-text="formatCurrency(summary.total_advances_owner)"></span>
                                </div>
                                <div
                                    class="flex justify-between text-xs font-bold text-gray-900 pt-1 border-t border-gray-100">
                                    <span class="uppercase">(=) Entregado:</span>
                                    <span x-text="formatCurrency(summary.total_advances)"></span>
                                </div>
                                <div
                                    class="flex justify-between text-xs text-red-600 font-bold border-b border-gray-100 pb-1">
                                    <span class="uppercase">(-) Gastos:</span>
                                    <span x-text="formatCurrency(summary.total_expenses)"></span>
                                </div>
                                <div
                                    class="flex justify-between font-black text-sm text-red-700 uppercase bg-red-50 px-3 py-1.5 rounded">
                                    <span>SALDO GESTIÓN:</span>
                                    <span x-text="formatCurrency(summary.balance_to_discount)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Signature -->
                    <div class="p-6 pt-0">
                        <div x-show="form.notes"
                            class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-[10px] italic text-gray-700">
                            <span class="font-black uppercase not-italic block mb-0.5">Notas / Observaciones:</span>
                            <span x-text="form.notes"></span>
                        </div>

                        <!-- Disclaimer / Aclaración -->
                        <div
                            class="mb-4 text-[8px] leading-tight text-gray-400 text-justify border-t border-gray-100 pt-2">
                            <p><span class="font-bold uppercase text-gray-500">Nota Aclaratoria:</span> Al conductor se
                                le cancela el 9% de comisión sobre el flete neto de cada viaje realizado, adicional a su
                                salario básico mensual pactado. El Auxilio de Transporte se liquidará y cancelará
                                únicamente en los periodos donde la sumatoria de las comisiones devengadas más el sueldo
                                básico no supere el equivalente a dos (2) salarios mínimos legales mensuales vigentes
                                (SMLMV), conforme a la normativa laboral vigente.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-8">
                            <div
                                class="mt-12 border-t border-gray-400 pt-1 text-center text-[9px] text-gray-500 uppercase font-black">
                                Firma del Conductor
                                <div class="mt-0.5 font-normal lowercase italic"
                                    x-text="'C.C: ' + personnel.document_number"></div>
                            </div>
                            <div class="mt-12 text-right print:hidden">
                                <button onclick="window.print()"
                                    class="bg-gray-900 text-white px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-black transition shadow-lg">Imprimir</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function settlementModule() {
        return {
            filters: {
                driver_id: '',
                date_start: '<?php echo date('Y-m-01'); ?>',
                date_end: '<?php echo date('Y-m-t'); ?>'
            },
            form: {
                partial_payment: 0,
                notes: ''
            },
            personnel: {},
            summary: {},
            trips: [],
            dataLoaded: false,
            isSaving: false,

            get calculatedNetPay() {
                if (!this.dataLoaded) return 0;
                return this.summary.net_to_pay - this.form.partial_payment;
            },

            async fetchData() {
                if (!this.filters.driver_id || !this.filters.date_start || !this.filters.date_end) {
                    this.dataLoaded = false;
                    return;
                }
                try {
                    const response = await fetch(`api_get_settlement_data.php?personnel_id=${this.filters.driver_id}&date_start=${this.filters.date_start}&date_end=${this.filters.date_end}`);
                    const data = await response.json();
                    if (data.error) throw new Error(data.error);

                    this.personnel = data.personnel;
                    this.summary = data.summary;
                    this.trips = data.trips;
                    this.dataLoaded = true;
                    this.form.partial_payment = 0; // Reset partial payment on fetch
                    this.form.notes = ''; // Reset notes on fetch
                } catch (error) {
                    console.error('Fetch Error:', error);
                    alert('Error al cargar datos: ' + error.message);
                }
            },

            async saveSettlement() {
                if (!confirm('¿Está seguro de que desea guardar esta liquidación con un Neto Final de ' + this.formatCurrency(this.calculatedNetPay) + '?')) return;
                this.isSaving = true;
                try {
                    const response = await fetch('save_settlement.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            personnel_id: this.filters.driver_id,
                            date_start: this.filters.date_start,
                            date_end: this.filters.date_end,
                            salary_basic: this.personnel.salary_basic,
                            transport_assistance: this.summary.transport_assistance_paid,
                            total_commissions: this.summary.total_commissions,
                            total_advances: this.summary.total_advances,
                            total_advances_manifest: this.summary.total_advances_manifest,
                            total_advances_owner: this.summary.total_advances_owner,
                            total_expenses: this.summary.total_expenses,
                            balance_to_discount: this.summary.balance_to_discount,
                            partial_payment: this.form.partial_payment,
                            notes: this.form.notes,
                            net_to_pay: this.calculatedNetPay // We save the FINAL net pay after abono
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Liquidación guardada exitosamente.');
                    } else {
                        throw new Error(result.error);
                    }
                } catch (error) {
                    alert('Error al guardar: ' + error.message);
                } finally {
                    this.isSaving = false;
                }
            },

            formatCurrency(value) {
                return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value);
            },

            formatDate(dateStr) {
                if (!dateStr) return '-';
                const [year, month, day] = dateStr.split('-');
                return `${day}/${month}/${year}`;
            }
        }
    }
</script>

<style>
    @media screen {
        #letter-container {
            width: 27.94cm;
            /* Letter height becomes width in landscape */
            min-height: 21.59cm;
            /* Letter width becomes height in landscape */
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
    }

    @media print {
        @page {
            size: letter landscape;
            margin: 0.8cm;
        }

        body * {
            visibility: hidden;
        }

        #paymentStub,
        #paymentStub * {
            visibility: visible;
        }

        #paymentStub {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            box-shadow: none !important;
        }

        .print\:hidden {
            display: none !important;
        }
    }

    /* Optimization for many rows */
    #paymentStub table {
        table-layout: auto;
    }

    #paymentStub th,
    #paymentStub td {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
        line-height: 1.1;
    }
</style>

<?php include 'includes/footer.php'; ?>