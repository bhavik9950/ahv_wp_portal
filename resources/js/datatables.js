import DataTable from 'datatables.net-dt';
import 'datatables.net-responsive-dt';

window.DataTable = DataTable;

/**
 * Initialise DataTables on any <table data-datatable>.
 *
 * Server-side (recommended for large tenant data):
 *   <table data-datatable
 *          data-ajax="{{ route('whatsapp.contacts.data') }}"
 *          data-columns='["name","phone_e164","opt_in_status"]'>
 *
 * Client-side: just <table data-datatable> with a full <tbody>.
 *
 * Exports (Excel / PDF) are handled by dedicated server endpoints
 * (Laravel Excel + dompdf), not client-side, so tenant scoping and
 * authorization always apply.
 */
window.initDataTables = () => {
    document.querySelectorAll('table[data-datatable]').forEach((table) => {
        if (table.dataset.dtInitialised) return;
        table.dataset.dtInitialised = '1';

        const ajax = table.dataset.ajax || null;
        const columns = table.dataset.columns
            ? JSON.parse(table.dataset.columns).map((name) => ({ data: name, name }))
            : undefined;

        new DataTable(table, {
            serverSide: Boolean(ajax),
            processing: Boolean(ajax),
            ajax: ajax ? { url: ajax, type: 'GET' } : undefined,
            columns,
            responsive: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [],
            language: { search: '', searchPlaceholder: 'Search…' },
        });
    });
};

document.addEventListener('DOMContentLoaded', () => window.initDataTables());
