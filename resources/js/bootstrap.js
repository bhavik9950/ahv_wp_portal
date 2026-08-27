import axios from 'axios';
import jQuery from 'jquery';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/* jQuery is needed by DataTables and toastr. */
window.$ = window.jQuery = jQuery;

/* Send the CSRF token with every jQuery AJAX request. */
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    jQuery.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': token },
    });
}
