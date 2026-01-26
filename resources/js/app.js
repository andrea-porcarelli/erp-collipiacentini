import './bootstrap';
import 'bootstrap';
import toastr from 'toastr';

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

// Rendi toastr disponibile globalmente
window.toastr = toastr;
