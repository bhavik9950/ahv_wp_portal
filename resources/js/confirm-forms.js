/**
 * Any <form data-confirm="Question text?"> asks for confirmation (SweetAlert2)
 * before submitting. An empty data-confirm value submits without prompting.
 */
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
    if (form.dataset.confirmed === '1') return;

    const text = form.dataset.confirm;
    if (!text) return;

    event.preventDefault();

    if (await window.confirmAction({ text, confirmButtonText: 'Yes, proceed' })) {
        form.dataset.confirmed = '1';
        form.submit();
    }
});
