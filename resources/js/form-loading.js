/**
 * Shows a busy state on form submit so the user knows the request is running
 * until the page navigates to the result (with its success / failure alert).
 *
 * Opt in per form:  <form data-loading> … </form>
 * Optional label:    <button data-loading-text="Sending…">Send</button>
 *                    <form data-loading data-loading-text="Sending…">
 *
 * The submit button(s) are disabled and swapped for a spinner. A normal submit
 * triggers a full navigation, so the busy state naturally persists until the
 * next page renders. If validation sends the user back, the fresh page resets it.
 */

const escapeHtml = (s) =>
    s.replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-loading')) return;
    // Another handler (e.g. the confirm dialog) stopped this submit — do nothing yet.
    if (event.defaultPrevented || form.dataset.loadingActive === '1') return;
    form.dataset.loadingActive = '1';

    const controls = new Set(
        form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]'),
    );
    if (event.submitter) controls.add(event.submitter);

    controls.forEach((el) => {
        const label =
            el.getAttribute('data-loading-text') ||
            form.getAttribute('data-loading-text') ||
            'Working…';

        if (el.tagName === 'INPUT') {
            el.dataset.originalValue = el.value;
            el.value = label;
        } else {
            el.dataset.originalHtml = el.innerHTML;
            el.innerHTML = `<span class="loading loading-spinner loading-sm"></span> ${escapeHtml(label)}`;
        }

        // Disable after the event dispatch so the submitter's name/value still posts.
        requestAnimationFrame(() => {
            el.disabled = true;
        });
    });
});

/* Restore the form if the page comes back from the bfcache (Back button). */
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;

    document.querySelectorAll('form[data-loading-active="1"]').forEach((form) => {
        delete form.dataset.loadingActive;

        form.querySelectorAll('[data-original-html]').forEach((el) => {
            el.disabled = false;
            el.innerHTML = el.dataset.originalHtml;
            delete el.dataset.originalHtml;
        });
        form.querySelectorAll('[data-original-value]').forEach((el) => {
            el.disabled = false;
            el.value = el.dataset.originalValue;
            delete el.dataset.originalValue;
        });
    });
});
