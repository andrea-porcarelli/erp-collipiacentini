import './bootstrap';
import 'bootstrap';
import toastr from 'toastr';
import Sortable from 'sortablejs';

// Configura toastr globalmente
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 5000,
    extendedTimeOut: 2000,
    showEasing: 'swing',
    hideEasing: 'linear',
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut'
};

// Rendi disponibili globalmente
window.toastr = toastr;
window.Sortable = Sortable;
