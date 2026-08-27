import Swal from 'sweetalert2';
import toastr from 'toastr';

toastr.options = {
    closeButton: true,
    progressBar: true,
    newestOnTop: true,
    positionClass: 'toast-top-right',
    timeOut: 5000,
    escapeHtml: true, // never render server strings as HTML
};

window.Swal = Swal;
window.toastr = toastr;

/**
 * Small helpers used across the app. All text is treated as plain text.
 */
window.notify = {
    success: (msg) => toastr.success(msg),
    info: (msg) => toastr.info(msg),
    warning: (msg) => toastr.warning(msg),
    error: (msg) => toastr.error(msg),
};

/**
 * Confirmation dialog. Resolves true/false.
 * Usage: if (await window.confirmAction({ text: 'Delete this contact?' })) { ... }
 */
window.confirmAction = async ({
    title = 'Are you sure?',
    text = '',
    confirmButtonText = 'Confirm',
    icon = 'warning',
} = {}) => {
    const result = await Swal.fire({
        title,
        text, // .text (not .html) — no HTML injection
        icon,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-ghost',
        },
        buttonsStyling: false,
    });

    return result.isConfirmed;
};

/* Flash messages set server-side via session('flash_notify'). */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('flash-notify');
    if (!el) return;
    try {
        const flash = JSON.parse(el.textContent || '{}');
        if (flash.type && flash.message && window.notify[flash.type]) {
            window.notify[flash.type](flash.message);
        }
    } catch (e) {
        /* ignore malformed flash payloads */
    }
});
