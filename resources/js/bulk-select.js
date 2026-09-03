/**
 * "Select all → bulk action" for a client-side DataTable, working across every
 * page (DataTables keeps off-page rows in memory even though it doesn't render
 * them).
 *
 *   <table data-datatable data-bulk> … </table>
 *       each row:  <input type="checkbox" class="js-bulk-row" value="<id>">
 *       header:    <input type="checkbox" class="js-bulk-all">
 *
 *   <div data-bulk-bar hidden> … <span data-bulk-count>0</span> … </div>
 *   <form data-bulk-ids> … </form>          (one or more; each gets contact_ids[])
 *
 * On submit every `data-bulk-ids` form is filled with one hidden
 * <input name="contact_ids[]"> per checked row — the whole filtered set, not
 * just the visible page.
 */

function selectedIdsFor(table) {
    const dt = table.dtInstance;
    if (!dt) return [];
    const ids = [];
    dt.rows({ search: 'applied' }).every(function () {
        const cb = this.node()?.querySelector('.js-bulk-row');
        if (cb && cb.checked) ids.push(cb.value);
    });
    return ids;
}

function allRowCheckboxes(table) {
    const dt = table.dtInstance;
    const boxes = [];
    dt.rows({ search: 'applied' }).every(function () {
        const cb = this.node()?.querySelector('.js-bulk-row');
        if (cb) boxes.push(cb);
    });
    return boxes;
}

function setup(table) {
    if (table.dataset.bulkWired || !table.dtInstance) return;
    table.dataset.bulkWired = '1';

    const bar = document.querySelector('[data-bulk-bar]');
    const countEl = bar?.querySelector('[data-bulk-count]');
    const allBox = table.querySelector('.js-bulk-all');

    const refresh = () => {
        const boxes = allRowCheckboxes(table);
        const checked = boxes.filter((b) => b.checked);
        if (countEl) countEl.textContent = String(checked.length);
        if (bar) bar.hidden = checked.length === 0;
        if (allBox) {
            allBox.checked = boxes.length > 0 && checked.length === boxes.length;
            allBox.indeterminate = checked.length > 0 && checked.length < boxes.length;
        }
    };

    allBox?.addEventListener('change', () => {
        allRowCheckboxes(table).forEach((b) => { b.checked = allBox.checked; });
        refresh();
    });

    table.addEventListener('change', (e) => {
        if (e.target.classList.contains('js-bulk-row')) refresh();
    });
    table.dtInstance.on('draw', refresh);

    refresh();
}

// Fill any bulk form with the current selection just before it submits.
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-bulk-ids')) return;

    const table = document.querySelector('table[data-bulk]');
    if (!table) return;

    form.querySelectorAll('input[data-bulk-generated]').forEach((el) => el.remove());
    selectedIdsFor(table).forEach((id) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'contact_ids[]';
        input.value = id;
        input.dataset.bulkGenerated = '1';
        form.appendChild(input);
    });
}, true); // capture — run before Turbo / form-loading handlers

document.addEventListener('turbo:load', () => {
    document.querySelectorAll('table[data-bulk]').forEach(setup);
});
document.addEventListener('datatable:init', (e) => {
    if (e.target.hasAttribute('data-bulk')) setup(e.target);
});
