/**
 * Form Persistence System (Auto-Draft)
 * Automatically saves form data to LocalStorage to prevent data loss on refresh/crash.
 */

const FormPersistence = {
    STORAGE_PREFIX: 'celr_draft_',
    SAVE_DELAY: 1000, // 1 second debounce
    saveTimeouts: {},

    init() {
        const forms = document.querySelectorAll('form.persist-form');
        forms.forEach(form => this.setupForm(form));

        // Check for cleanup flag in URL (optional mechanism for controllers)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('clear_draft')) {
            const formId = urlParams.get('clear_draft');
            this.clear(formId);
            // Clean URL
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({ path: newUrl }, '', newUrl);
        }
    },

    setupForm(form) {
        if (!form.id) {
            console.warn('Form Persistence: Form missing ID attribute, skipping.', form);
            return;
        }

        const storageKey = this.getStorageKey(form.id);
        const statusElement = this.createStatusElement(form);

        // 1. Restaurar datos al cargar
        this.loadFormData(form, storageKey, statusElement);

        // 2. Escuchar cambios
        form.addEventListener('input', (e) => {
            // Ignorar password y hidden (a veces)
            if (e.target.type === 'password') return;

            this.showStatus(statusElement, 'Guardando...', 'text-slate-400');

            clearTimeout(this.saveTimeouts[form.id]);
            this.saveTimeouts[form.id] = setTimeout(() => {
                this.saveFormData(form, storageKey, statusElement);
            }, this.SAVE_DELAY);
        });

        // 3. Limpiar al enviar (Asumimos éxito si se dispara submit sin prevenir)
        form.addEventListener('submit', () => {
            // Solo limpiar si no hay prevención inmediata (ej. validación HTML5 pasa)
            // Usamos un pequeño delay o simplemente limpiamos.
            // Riesgo: Si falla envío ajax, perdemos draft. 
            // Para forms tradicionales POST, limpiar aquí es lo estándar.
            localStorage.removeItem(storageKey);
        });
    },

    getStorageKey(formId) {
        // Clave única por URL (incluyendo parámetros de búsqueda para distinguir entre diferentes registros en edición) y Form ID
        return `${this.STORAGE_PREFIX}${window.location.pathname}${window.location.search}_${formId}`;
    },

    saveFormData(form, key, statusElement) {
        const formData = new FormData(form);
        const data = {};

        // Convertir FormData a objeto simple
        // Nota: No guarda archivos (input type=file), solo texto.
        for (const [name, value] of formData.entries()) {
            // Manejo especial para arrays (checkboxes multi)
            if (data[name]) {
                if (!Array.isArray(data[name])) {
                    data[name] = [data[name]];
                }
                data[name].push(value);
            } else {
                data[name] = value;
            }
        }

        // Agregar timestamp
        data._timestamp = new Date().getTime();

        try {
            localStorage.setItem(key, JSON.stringify(data));
            this.showStatus(statusElement, '✓ Borrador guardado', 'text-green-600');
            setTimeout(() => {
                this.showStatus(statusElement, '', ''); // Ocultar después de un rato
            }, 3000);
        } catch (e) {
            console.error('Error saving draft', e);
        }
    },

    MAX_DRAFT_AGE: 2 * 60 * 60 * 1000, // 2 hours in ms

    loadFormData(form, key, statusElement) {
        const saved = localStorage.getItem(key);
        if (!saved) return;

        try {
            const data = JSON.parse(saved);

            // Check for expiration (Draft must be newer than 2 hours)
            const now = new Date().getTime();
            if (data._timestamp && (now - data._timestamp > this.MAX_DRAFT_AGE)) {
                console.log('Form Persistence: Draft expired, ignoring.');
                localStorage.removeItem(key);
                return;
            }
            const inputs = form.elements;
            let restoredCount = 0;

            for (let i = 0; i < inputs.length; i++) {
                const input = inputs[i];
                const name = input.name;

                if (!name || data[name] === undefined) continue;
                if (input.type === 'file' || input.type === 'password' || input.type === 'submit') continue;

                // Safety: Do not overwrite a pre-filled field (from PHP) with an empty value from a draft.
                if (data[name] === "" && input.value !== "" && input.value !== "0") continue;

                // Solo restaurar si el campo está vacío (para no sobreescribir valores PHP)
                // Opcional: Forzar restauración.
                // Decisión: Restaurar siempre por ahora, ya que es la intención de "recuperar trabajo perdido".
                // Excepto si el valor PHP ya existe y es distinto? Es complejo.
                // Asumimos que si hay draft, es más nuevo que el server default.

                if (input.type === 'radio') {
                    if (input.value == data[name]) {
                        input.checked = true;
                        input.dispatchEvent(new Event('change')); // Trigger Alpine/Listeners
                        restoredCount++;
                    }
                } else if (input.type === 'checkbox') {
                    if (Array.isArray(data[name])) {
                        input.checked = data[name].includes(input.value);
                    } else {
                        input.checked = (input.value == data[name]);
                    }
                    input.dispatchEvent(new Event('change'));
                    restoredCount++;
                } else {
                    input.value = data[name];
                    input.dispatchEvent(new Event('input')); // Trigger Alpine/Listeners
                    input.dispatchEvent(new Event('change'));
                    restoredCount++;
                }
            }

            if (restoredCount > 0) {
                this.showStatus(statusElement, '⟳ Borrador restaurado', 'text-brand-600');
                // Disparar evento global por si Alpine necesita recalcular todo
                window.dispatchEvent(new Event('form-restored'));
            }

        } catch (e) {
            console.error('Error loading draft', e);
        }
    },

    createStatusElement(form) {
        // Crear un indicador cerca del botón de submit o al final del form
        // Buscamos los botones de acción
        let container = form.querySelector('.form-actions') || form.querySelector('button[type="submit"]')?.parentNode;

        if (!container) {
            container = form;
        }

        const status = document.createElement('div');
        status.className = 'text-xs font-medium transition-all duration-300 h-4 mt-2 text-right opacity-0';
        status.style.minHeight = '1rem';

        // Insertar antes del contenedor de botones si es posible, o dentro
        container.appendChild(status);

        return status;
    },

    showStatus(el, msg, colorClass) {
        if (!el) return;
        el.textContent = msg;
        el.className = `text-xs font-medium transition-all duration-300 h-4 mt-2 text-right ${colorClass}`;
        el.style.opacity = msg ? '1' : '0';
    },

    clear(formId) {
        const key = this.getStorageKey(formId);
        localStorage.removeItem(key);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    FormPersistence.init();
});
