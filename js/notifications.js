/**
 * CELR Notification System
 * Toast notifications + Alert cards estilo 3D
 */

const Notify = (() => {

    const ICONS = {
        success: '✅',
        error:   '❌',
        warning: '⚠️',
        info:    'ℹ️',
    };

    const TITLES = {
        success: 'Éxito',
        error:   'Error',
        warning: 'Atención',
        info:    'Información',
    };

    let container = null;

    function getContainer() {
        if (!container) {
            container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                document.body.appendChild(container);
            }
        }
        return container;
    }

    /**
     * Muestra un toast
     * @param {string} message  - Texto del mensaje
     * @param {string} type     - 'success' | 'error' | 'warning' | 'info'
     * @param {string} title    - Título opcional (usa default si no se pasa)
     * @param {number} duration - ms antes de auto-cerrar (0 = no cierra solo)
     */
    function show(message, type = 'info', title = null, duration = 4000) {
        const c     = getContainer();
        const toast = document.createElement('div');
        const icon  = ICONS[type]  || ICONS.info;
        const ttl   = title || TITLES[type] || 'Aviso';

        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <div class="toast-body">
                <div class="toast-title">${ttl}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button onclick="this.closest('.toast').remove()" style="
                background:none;border:none;cursor:pointer;
                opacity:0.6;font-size:1rem;padding:0;line-height:1;
                color:inherit;flex-shrink:0;align-self:flex-start;
                margin-top:2px;
            ">✕</button>
            ${duration > 0 ? `<div class="toast-progress" style="animation-duration:${duration}ms"></div>` : ''}
        `;

        toast.addEventListener('click', () => dismiss(toast));
        c.appendChild(toast);

        // Animar entrada
        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('show'));
        });

        if (duration > 0) {
            setTimeout(() => dismiss(toast), duration);
        }

        return toast;
    }

    function dismiss(toast) {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 350);
    }

    // Atajos
    const success = (msg, title, dur)  => show(msg, 'success', title, dur);
    const error   = (msg, title, dur)  => show(msg, 'error',   title, dur ?? 6000);
    const warning = (msg, title, dur)  => show(msg, 'warning', title, dur);
    const info    = (msg, title, dur)  => show(msg, 'info',    title, dur);

    /**
     * Convierte un div de alerta PHP inline en una alert-card bonita
     * Uso: Notify.upgradeAlerts() al cargar la página
     */
    function upgradeAlerts() {
        // Mapeo de clases antiguas → nuevas
        const map = [
            { old: ['bg-green-100', 'border-green-'],  type: 'success', icon: '✅', title: 'Operación exitosa' },
            { old: ['bg-red-100',   'border-red-'],    type: 'error',   icon: '❌', title: 'Error' },
            { old: ['bg-yellow-100','border-yellow-'], type: 'warning', icon: '⚠️', title: 'Atención' },
            { old: ['bg-blue-100',  'border-blue-'],   type: 'info',    icon: 'ℹ️', title: 'Información' },
        ];

        // Seleccionar divs de alerta inline típicos del proyecto
        const candidates = document.querySelectorAll(
            '[class*="bg-green-100"], [class*="bg-red-100"], [class*="bg-yellow-100"], [class*="bg-blue-100"]'
        );

        candidates.forEach(el => {
            // Solo convertir si parece un banner de mensaje (tiene border)
            const cls = el.className;
            if (!cls.includes('border')) return;
            // Evitar convertir tarjetas de dashboard, tablas, etc.
            if (el.tagName !== 'DIV') return;
            if (el.closest('table') || el.closest('form')) return;

            let matched = null;
            for (const m of map) {
                if (m.old.some(c => cls.includes(c))) { matched = m; break; }
            }
            if (!matched) return;

            const originalText = el.textContent.trim();
            el.outerHTML = buildAlertCard(matched.type, matched.icon, matched.title, originalText);
        });
    }

    function buildAlertCard(type, icon, title, text) {
        return `
        <div class="alert-card alert-card-${type}">
            <span class="alert-card-icon">${icon}</span>
            <div class="alert-card-body">
                <div class="alert-card-title">${title}</div>
                <div class="alert-card-text">${text}</div>
            </div>
            <button class="alert-card-close" onclick="this.closest('.alert-card').remove()">✕</button>
        </div>`;
    }

    /**
     * Crea una alert-card directamente en el DOM
     * @param {string} containerId  - ID del elemento donde insertar
     * @param {string} type         - success | error | warning | info | neutral
     * @param {string} title
     * @param {string} text
     */
    function alertCard(containerId, type, title, text) {
        const icons = { success:'✅', error:'❌', warning:'⚠️', info:'ℹ️', neutral:'📋' };
        const el = document.getElementById(containerId);
        if (!el) return;
        el.insertAdjacentHTML('afterbegin', buildAlertCard(type, icons[type] || '📋', title, text));
    }

    // Auto-upgrade al cargar si existe algún banner antiguo
    document.addEventListener('DOMContentLoaded', () => {
        upgradeAlerts();

        // Leer mensajes flash desde meta tags (para PHP → JS)
        const flashEl = document.getElementById('flash-messages');
        if (flashEl) {
            const msgs = JSON.parse(flashEl.dataset.messages || '[]');
            msgs.forEach(m => show(m.text, m.type, m.title));
        }
    });

    return { show, success, error, warning, info, upgradeAlerts, alertCard, buildAlertCard };
})();

// Global shortcuts
window.Notify = Notify;
