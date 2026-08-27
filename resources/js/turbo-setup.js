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
document.addEventListener('turbo:load', () => {
    clearInterval(autoRefreshTimer);
    const el = document.querySelector('[data-auto-refresh]');
    const seconds = el ? parseInt(el.dataset.autoRefresh, 10) : 0;
    if (!seconds || seconds < 2) return;

    autoRefreshTimer = setInterval(() => {
        if (document.visibilityState !== 'visible' || document.querySelector('.turbo-progress-bar')) return;

        const y = window.scrollY;
        document.addEventListener('turbo:load', () => window.scrollTo(0, y), { once: true });
        window.Turbo.visit(window.location.href, { action: 'replace' });
    }, seconds * 1000);
});
document.addEventListener('turbo:before-cache', () => clearInterval(autoRefreshTimer));
