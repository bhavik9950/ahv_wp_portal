/**
 * Turbo Drive configuration.
 *
 * Turbo turns every same-origin link/form into a background fetch + <body> swap,
 * so the sidebar/topbar never repaint and there is no white "reload" flash.
 *
 * Notes for the rest of the app:
 *   - Per-page setup code runs on `turbo:load` (fires on first load AND every
 *     navigation), never `DOMContentLoaded` (fires once).
 *   - Widgets that attach to elements (DataTables, charts) tear down on
 *     `turbo:before-cache` so a cached snapshot doesn't keep a dead instance.
 *   - Links that download a file opt out with `data-turbo="false"`.
 */

// Show the top progress bar quickly on a slow response.
window.Turbo.config.drive.progressBarDelay = 150;

// Client-side widgets register a teardown here; run them before Turbo caches
// the page so the snapshot is clean.
window.__turboTeardowns = window.__turboTeardowns || new Set();
document.addEventListener('turbo:before-cache', () => {
    window.__turboTeardowns.forEach((fn) => {
        try {
            fn();
        } catch (e) {
            /* ignore */
        }
    });
});

// Keep jQuery's CSRF header in sync if the token changes (e.g. after login).
document.addEventListener('turbo:load', () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token && window.jQuery) {
        window.jQuery.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
        if (window.axios) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
});

/**
 * Live pages opt in with <div data-auto-refresh="60"> — re-fetch via Turbo
 * (in-place, no flash) instead of a hard <meta http-equiv="refresh">.
 */
let autoRefreshTimer;
window._autoRefreshPaused = false;

function shouldSkipAutoRefresh() {
    // 1. Manually paused by user
    if (window._autoRefreshPaused) return true;

    // 2. An input, select, or textarea is currently focused/active
    const active = document.activeElement;
    if (active && (active.tagName === 'INPUT' || active.tagName === 'SELECT' || active.tagName === 'TEXTAREA')) {
        return true;
    }

    // 3. User has typed in a DataTables search box
    const searchInputs = document.querySelectorAll('.dt-search input');
    for (const input of searchInputs) {
        if (input.value && input.value.trim() !== '') return true;
    }

    // 4. User has selected a non-empty table filter (e.g. status != all)
    const filters = document.querySelectorAll('[data-dt-filter]');
    for (const filter of filters) {
        if (filter.value && filter.value.trim() !== '') return true;
    }

    return false;
}

function updateAutoRefreshToggleUI() {
    const btn = document.querySelector('[data-auto-refresh-toggle]');
    if (!btn) return;

    const indicator = btn.querySelector('[data-auto-refresh-indicator]');
    const label = btn.querySelector('[data-auto-refresh-label]');

    const hasActiveFilter = Array.from(document.querySelectorAll('[data-dt-filter]')).some(f => f.value && f.value.trim() !== '')
        || Array.from(document.querySelectorAll('.dt-search input')).some(i => i.value && i.value.trim() !== '');

    if (window._autoRefreshPaused) {
        if (indicator) indicator.className = 'w-2 h-2 rounded-full bg-error';
        if (label) label.textContent = 'Auto-refresh: Paused';
    } else if (hasActiveFilter) {
        if (indicator) indicator.className = 'w-2 h-2 rounded-full bg-warning';
        if (label) label.textContent = 'Auto-refresh: Paused (filter active)';
    } else {
        const el = document.querySelector('[data-auto-refresh]');
        const s = el ? parseInt(el.dataset.autoRefresh, 10) : 10;
        if (indicator) indicator.className = 'w-2 h-2 rounded-full bg-success animate-pulse';
        if (label) label.textContent = `Auto-refresh: ON (${s}s)`;
    }
}

document.addEventListener('turbo:load', () => {
    clearInterval(autoRefreshTimer);
    const el = document.querySelector('[data-auto-refresh]');
    const seconds = el ? parseInt(el.dataset.autoRefresh, 10) : 0;
    if (!seconds || seconds < 2) return;

    updateAutoRefreshToggleUI();

    autoRefreshTimer = setInterval(() => {
        if (document.visibilityState !== 'visible' || document.querySelector('.turbo-progress-bar')) return;
        if (shouldSkipAutoRefresh()) {
            updateAutoRefreshToggleUI();
            return;
        }

        const y = window.scrollY;
        document.addEventListener('turbo:load', () => window.scrollTo(0, y), { once: true });

        // Suppress view transitions and progress bar during background auto-refresh
        document.documentElement.classList.add('no-view-transition');
        document.addEventListener('turbo:load', () => {
            requestAnimationFrame(() => {
                document.documentElement.classList.remove('no-view-transition');
            });
        }, { once: true });

        window.Turbo.visit(window.location.href, { action: 'replace' });
    }, seconds * 1000);
});

// Delegated listener for toggle button
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-auto-refresh-toggle]');
    if (!btn) return;
    e.preventDefault();
    window._autoRefreshPaused = !window._autoRefreshPaused;
    updateAutoRefreshToggleUI();
});

// Update UI when filters change
document.addEventListener('change', (e) => {
    if (e.target.matches('[data-dt-filter]') || e.target.closest('.dt-search')) {
        updateAutoRefreshToggleUI();
    }
});

document.addEventListener('input', (e) => {
    if (e.target.closest('.dt-search')) {
        updateAutoRefreshToggleUI();
    }
});

document.addEventListener('turbo:before-cache', () => clearInterval(autoRefreshTimer));
