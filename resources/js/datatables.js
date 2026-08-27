import DataTable from 'datatables.net-dt';
import 'datatables.net-responsive-dt';

window.DataTable = DataTable;

/**
 * Zero-config client-side tables.
 *
 *   <table data-datatable id="tpl-table"> … </table>
 *
 * gives you search, column sorting, paging and a page-length selector for free.
 *
 * Optional attributes on the <table>:
 *   data-page-length="25"          initial rows per page
 *   data-order='[[4,"desc"]]'      initial sort ([colIndex, "asc"|"desc"])
 *   data-no-sort="0,5"             columns that must not be sortable
 *   data-searchable="false"        hide the built-in search box
 *
 * External dropdown / text filters (no per-page JS needed):
 *
 *   <select data-dt-filter data-dt-target="#tpl-table" data-dt-col="3">
 *       <option value="">All</option>
 *       <option value="APPROVED">Approved</option>
 *   </select>
 *
 *   data-dt-target   CSS selector of the table (defaults to the closest one)
 *   data-dt-col      column index to filter, OR
 *   data-dt-col-name matches a <th> by its text
 *   data-dt-match    "exact" (default) or "contains"
 *
 * Server-side mode (large tenant data) is still available via data-ajax +
 * data-columns; exports stay on dedicated Laravel endpoints.
 */

const registry = new Map();

const escapeRegExp = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

function columnIndex(table, ctrl) {
    if (ctrl.dataset.dtCol !== undefined && ctrl.dataset.dtCol !== '') {
        return Number(ctrl.dataset.dtCol);
    }
    const name = (ctrl.dataset.dtColName || '').trim().toLowerCase();
    if (!name) return null;
    const headers = [...table.querySelectorAll('thead th')];
    const i = headers.findIndex((th) => th.textContent.trim().toLowerCase() === name);
    return i === -1 ? null : i;
}

function wireFilter(ctrl) {
    const selector = ctrl.dataset.dtTarget;
    const table = selector
        ? document.querySelector(selector)
        : ctrl.closest('.dt-scope')?.querySelector('table[data-datatable]')
            || document.querySelector('table[data-datatable]');

    const dt = table && registry.get(table);
    if (!dt) return;

    const col = columnIndex(table, ctrl);
    if (col === null) return;

    const contains = ctrl.dataset.dtMatch === 'contains';
    const apply = () => {
        const value = ctrl.value.trim();
        const term = value === ''
            ? ''
            : (contains ? escapeRegExp(value) : `^\\s*${escapeRegExp(value)}\\s*$`);
        dt.column(col).search(term, { regex: term !== '', smart: false }).draw();
    };

    ctrl.addEventListener('change', apply);
    ctrl.addEventListener('input', apply);
    apply();
}

window.initDataTables = () => {
    document.querySelectorAll('table[data-datatable]').forEach((table) => {
        if (table.dataset.dtInitialised) return;
        table.dataset.dtInitialised = '1';

        const ajax = table.dataset.ajax || null;
        const columns = table.dataset.columns
            ? JSON.parse(table.dataset.columns).map((name) => ({ data: name, name }))
            : undefined;

        const noSort = (table.dataset.noSort || '')
            .split(',')
            .map((n) => n.trim())
            .filter((n) => n !== '')
            .map(Number)
            .filter((n) => !Number.isNaN(n));

        const dt = new DataTable(table, {
            serverSide: Boolean(ajax),
            processing: Boolean(ajax),
            ajax: ajax ? { url: ajax, type: 'GET' } : undefined,
            columns,
            columnDefs: noSort.length ? [{ targets: noSort, orderable: false }] : [],
            responsive: true,
            pageLength: Number(table.dataset.pageLength || 25),
            lengthMenu: [10, 25, 50, 100],
            order: table.dataset.order ? JSON.parse(table.dataset.order) : [],
            searching: table.dataset.searchable !== 'false',
            language: {
                search: '',
                searchPlaceholder: 'Search…',
                lengthMenu: '_MENU_ per page',
                info: 'Showing _START_–_END_ of _TOTAL_',
                infoEmpty: 'Nothing to show',
                infoFiltered: '(of _MAX_)',
                emptyTable: 'Nothing here yet',
                zeroRecords: 'No matching rows',
            },
        });

        registry.set(table, dt);
    });

    document.querySelectorAll('[data-dt-filter]').forEach((ctrl) => {
        if (ctrl.dataset.dtWired) return;
        ctrl.dataset.dtWired = '1';
        wireFilter(ctrl);
    });
};

document.addEventListener('DOMContentLoaded', () => window.initDataTables());
