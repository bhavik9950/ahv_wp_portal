/**
 * Handles dashboard filter presets, custom date changes, and campaign selection
 * via Turbo-friendly form submission without inline CSP violations.
 */

function submitDashboardFilter() {
    const form = document.getElementById('dashboard_filter_form');
    if (!form) return;
    form.classList.add('opacity-70', 'pointer-events-none');
    if (form.requestSubmit) {
        form.requestSubmit();
    } else {
        form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        form.submit();
    }
}

// Expose globally for backward compatibility with existing inline calls
window.submitDashboardFilter = submitDashboardFilter;
window.setDashboardPreset = function(presetKey) {
    const hidden = document.getElementById('filter_range_key');
    if (hidden) hidden.value = presetKey;
    submitDashboardFilter();
};

// Delegated event listener for buttons and dropdowns
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-preset-key]');
    if (!btn) return;
    e.preventDefault();
    const hidden = document.getElementById('filter_range_key');
    if (hidden) hidden.value = btn.dataset.presetKey;
    submitDashboardFilter();
});

document.addEventListener('change', (e) => {
    if (e.target.id === 'filter_campaign_id' || e.target.hasAttribute('data-filter-campaign')) {
        submitDashboardFilter();
    }
});
