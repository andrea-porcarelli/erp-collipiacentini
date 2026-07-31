import './bootstrap';
import * as bootstrap from 'bootstrap';
import toastr from 'toastr';
import Sortable from 'sortablejs';

window.bootstrap = bootstrap;

// Configura toastr globalmente
toastr.options = {
    closeButton: false,
    progressBar: false,
    positionClass: 'toast-top-right',
    timeOut: 5000,
    extendedTimeOut: 2000,
    showEasing: 'swing',
    hideEasing: 'linear',
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut'
};

// Applica il data-mode del design system Miticko ai toast (snackbarAppearance-*)
const snackbarModeByType = {
    success: 'snackbarAppearance-Success',
    error: 'snackbarAppearance-Error',
    warning: 'snackbarAppearance-Warning',
    info: 'snackbarAppearance-Info',
};
Object.keys(snackbarModeByType).forEach((type) => {
    const original = toastr[type];
    toastr[type] = function () {
        const $el = original.apply(toastr, arguments);
        if ($el && $el.length) {
            $el.attr('data-mode', snackbarModeByType[type]);
        }
        return $el;
    };
});

// Rendi disponibili globalmente
window.toastr = toastr;
window.Sortable = Sortable;
