/**
 * Clicking anywhere on a <tr data-href="/path"> navigates there.
 * Ignores clicks on links, buttons and form controls inside the row.
 * CSP-safe: single delegated listener, no inline handlers.
 */
document.addEventListener('click', (event) => {
    const row = event.target.closest('tr[data-href]');
    if (!row) return;
    if (event.target.closest('a, button, input, label, select, form')) return;
    window.location = row.dataset.href;
});
