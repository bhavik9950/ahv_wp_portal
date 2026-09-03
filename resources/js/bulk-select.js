/**
 * "Select all → bulk action" for a client-side DataTable, working across every
 * page (DataTables keeps off-page rows in memory even though it doesn't render
 * them).
 *
 *   <table data-datatable data-bulk="#contact-bulk-form"> … </table>
 *       each row:  <input type="checkbox" class="js-bulk-row" value="<id>">
 *       header:    <input type="checkbox" class="js-bulk-all">
 *
 *   <div data-bulk-bar hidden> … <span data-bulk-count>0</span> … </div>
 *   <form id="contact-bulk-form" data-bulk-ids> … </form>
 *
 * On submit the form is filled with one hidden <input name="contact_ids[]"> per
 * checked row (from the whole filtered set, not just the visible page).
 */

function setup(table) {
    if (table.dataset.bulkWired) return;
    const dt = table.dtInstance;
    if (!dt) return;
    table.dataset.bulkWired = '1';

    const form = document.querySelector(table.dataset.bulk);
    const bar = document.querySelector('[data-bulk-bar]');
    const countEl = bar?.querySelector('[data-bulk-count]');
    const allBox = table.querySelector('.js-bulk-all');

    const checkboxes = () => {
        const boxes = [];
        dt.rows({ search: 'applied' }).every(function () {
            const cb = this.node()?.querySelector('.js-bulk-row');
            if (cb) boxes.push(cb);
        });
        return boxes;
    };

    const refresh = () => {
        const boxes = checkboxes();
        const checked = boxes.filter((b) => b.checked);
        if (countEl) countEl.textContent = String(checked.length);
        if (bar) bar.hidden = checked.length === 0;
        if (allBox) {
            allBox.checked = boxes.length > 0 && checked.length === boxes.length;
            allBox.indeterminate = checked.length > 0 && checked.length < boxes.length;
        }
    };

    allBox?.addEventListener('change', () => {
        checkboxes().forEach((b) => { b.checked = allBox.checked; });
        refresh();
    });

    // Row checkbox toggled (works even for a row on another page via delegation
    // on the table, plus a re-check after every draw).
    table.addEventListener('change', (e) => {
        if (e.target.classList.contains('js-bulk-row')) refresh();
    });
    dt.on('draw', refresh);

    form?.addEventListener('submit', () => {
        form.querySelectorAll('input[data-bulk-generated]').forEach((el) => el.remove());
        checkboxes().filter((b) => b.checked).forEach((b) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'contact_ids[]';
            input.value = b.value;
            input.dataset.bulkGenerated = '1';
            form.appendChild(input);
        });
    });

    refresh();
}

document.addEventListener('turbo:load', () => {
    document.querySelectorAll('table[data-bulk]').forEach(setup);
});
// A table may finish initialising after turbo:load.
document.addEventListener('datatable:init', (e) => {
    const table = e.target;
    if (table.hasAttribute('data-bulk')) setup(table);
});
