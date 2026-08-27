/**
 * The sidebar is `data-turbo-permanent` (it never repaints between pages), so
 * Turbo won't refresh its active-link highlight. Do it here on every navigation,
 * and close the mobile drawer after a link is chosen.
 */
function syncSidebar() {
    const here = window.location.pathname.replace(/\/+$/, '') || '/';
    const links = [...document.querySelectorAll('#app-sidebar a[href]')];

    const path = (link) => {
        try {
            return new URL(link.href).pathname.replace(/\/+$/, '') || '/';
        } catch (e) {
            return null;
        }
    };

    // Prefer an exact match; otherwise the deepest section the page sits under.
    let best = null;
    let bestLen = -1;
    links.forEach((link) => {
        const p = path(link);
        if (p === null || p === '/') return;
        if (p === here) {
            best = link;
            bestLen = Infinity;
        } else if (here.startsWith(`${p}/`) && p.length > bestLen) {
            best = link;
            bestLen = p.length;
        }
    });

    links.forEach((link) => {
        const active = link === best;
        link.classList.toggle('active', active);
        link.classList.toggle('font-medium', active);
    });

    const drawer = document.getElementById('app-drawer');
    if (drawer) drawer.checked = false;
}

document.addEventListener('turbo:load', syncSidebar);
