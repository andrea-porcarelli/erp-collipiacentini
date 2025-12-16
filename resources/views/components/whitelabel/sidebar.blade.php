@props([
    'company' => null
])
<x-card :title="__('whitelabel.sidebar.know_date_title')" :sub_title="__('whitelabel.sidebar.know_date_subtitle')" class="card-spacing">
    <x-button :label="__('whitelabel.sidebar.select_date')" leading="fa-calendar" status="secondary" emphasis="light" type="resting" size="small" class="btn-open-calendar"/>
    <input type="hidden" name="filter_date" />
</x-card>
<div class="d-none d-sm-block">
    <x-card :title="__('whitelabel.sidebar.castle_title')" class="card-spacing">
        @isset($company)
            {{ $company->company_name }}
        @endisset
    </x-card>

    <x-card :title="__('whitelabel.sidebar.useful_links_title')" class="card-spacing">
        <ul class="utils">
            <li><a href="#">{{ __('whitelabel.sidebar.contacts') }}</a></li>
            <li><a href="#">{{ __('whitelabel.sidebar.privacy_policy') }}</a></li>
        </ul>
    </x-card>
</div>

<!-- Modale per la selezione della data -->
<div class="modal fade" id="datePickerModal" tabindex="-1" aria-labelledby="datePickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="datePickerModalLabel">{{ __('whitelabel.sidebar.select_date') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="datepicker"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnOpenCalendar = document.querySelector('.btn-open-calendar');
    const datePickerModal = new bootstrap.Modal(document.getElementById('datePickerModal'));
    const datepickerContainer = document.getElementById('datepicker');

    let selectedDate = null;
    let pickerInstance = null;
    const originalLabel = btnOpenCalendar.textContent.trim();

    // Apri la modale al click sul bottone o resetta se è selezionata una data
    btnOpenCalendar.addEventListener('click', function(e) {
        // Se il bottone contiene l'icona X, resetta invece di aprire la modale
        if (e.target.classList.contains('fa-xmark') || e.target.closest('.fa-xmark')) {
            // Ripristina lo stato originale del bottone
            btnOpenCalendar.innerHTML = '<i class="fa-regular fa-calendar icon"></i> ' + originalLabel;
            btnOpenCalendar.classList.add('bt-m-light');

            // Svuota il campo filter_date
            const filterDateInput = document.querySelector('input[name="filter_date"]');
            if (filterDateInput) {
                filterDateInput.value = '';
            }

            // Resetta la data selezionata
            selectedDate = null;

            // Ricarica i prodotti senza filtro data
            if (typeof window.load_products === 'function') {
                window.load_products();
            }

            return;
        }

        // Altrimenti apri la modale
        datePickerModal.show();
    });

    // Inizializza Flatpickr quando la modale viene mostrata
    document.getElementById('datePickerModal').addEventListener('shown.bs.modal', function() {
        if (!pickerInstance) {
            pickerInstance = flatpickr(datepickerContainer, {
                inline: true,
                mode: 'single',
                dateFormat: 'Y-m-d',
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0) {
                        const date = selectedDates[0];
                        selectedDate = date.toLocaleDateString('it-IT', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                        const dateFormatted = dateStr;

                        // Popola il campo filter_date
                        const filterDateInput = document.querySelector('input[name="filter_date"]');
                        if (filterDateInput) {
                            filterDateInput.value = dateFormatted;
                        }

                        // Cambia lo stile del bottone
                        btnOpenCalendar.classList.remove('bt-m-light');

                        // Cambia il testo del bottone con HTML
                        btnOpenCalendar.innerHTML = '<i class="fa-regular fa-xmark"></i> ' + selectedDate;

                        // Chiama la funzione load_products se disponibile
                        if (typeof window.load_products === 'function') {
                            window.load_products();
                        }

                        // Chiudi la modale
                        datePickerModal.hide();
                    }
                }
            });
        }
    });
});
</script>

<style>
.btn-open-calendar.date-selected {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: white !important;
}

.flatpickr-calendar {
    font-family: 'DM Sans', sans-serif;
}
#datepicker .flatpickr-calendar {
    position: static !important;
    box-shadow: none !important;
}
#datePickerModal .modal-body {
    display: flex;
    justify-content: center;
}
</style>
@endpush
