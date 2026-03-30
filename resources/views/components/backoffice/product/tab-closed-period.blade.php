@props(['model'])

<div class="tab-pane fade" id="closed-period-panel" role="tabpanel" aria-labelledby="closed-period-tab-tab">
    <div class="row">
        <div class="col-12">
            <x-card class="position-relative">
                <div class="button-card-absolute">
                    <x-button label="Crea chiusura +" emphasis="High" leading="fa-plus" class="btn-create-closed-period" />
                </div>
                <div>
                    <h3 class="mb-0" style="font-size:18px;font-weight:600">Gestisci i periodi di chiusura</h3>
                    <p class="text-secondary small mt-2 mb-0">I periodi di chiusura disabiliteranno le date presenti sul calendario e i visitatori non potranno prenotare</p>
                </div>

                <div id="closed-periods-list" class="mt-spacing-xl">
                    @forelse($model->closedPeriods as $period)
                        <div class="closed-period-item d-flex align-items-center gap-3 py-2 border-bottom" data-id="{{ $period->id }}">
                            <i class="fa-regular fa-lock text-secondary"></i>
                            <span class="flex-grow-1">
                                {{ $period->date_from->locale('it')->isoFormat('D MMMM YYYY') }}
                                –
                                {{ $period->date_to->locale('it')->isoFormat('D MMMM YYYY') }}
                            </span>
                            <button type="button" class="bt-miticko btn-closed-period-delete" data-mode="small primary bt-m-text-only">
                                <i class="fa-regular fa-trash icon"></i>
                            </button>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0" id="closed-periods-empty">Nessun periodo di chiusura configurato.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const PRODUCT_ID = window.PRODUCT_ID;
    let _fpClosed = null;

    $(document).on('click', '.btn-create-closed-period', function () {
        $('#closed-period-from-val').val('');
        $('#closed-period-to-val').val('');
        $('#closed-period-range-label').text('Nessun periodo selezionato');
        $('#modal-closed-period').modal('show');
    });

    $('#modal-closed-period').on('shown.bs.modal', function () {
        if (_fpClosed) { _fpClosed.destroy(); _fpClosed = null; }
        _fpClosed = flatpickr('#closed-period-flatpickr', {
            mode: 'range',
            locale: 'it',
            dateFormat: 'Y-m-d',
            inline: true,
            onChange: function (dates) {
                if (dates.length === 2) {
                    const fmt = (d) => d.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' });
                    $('#closed-period-from-val').val(dates[0].toISOString().slice(0, 10));
                    $('#closed-period-to-val').val(dates[1].toISOString().slice(0, 10));
                    $('#closed-period-range-label').text(fmt(dates[0]) + ' – ' + fmt(dates[1]));
                }
            },
        });
    });

    $('#modal-closed-period').on('hidden.bs.modal', function () {
        if (_fpClosed) { _fpClosed.destroy(); _fpClosed = null; }
    });

    $(document).on('click', '#btn-confirm-closed-period', function () {
        const dateFrom = $('#closed-period-from-val').val();
        const dateTo   = $('#closed-period-to-val').val();
        if (!dateFrom || !dateTo) {
            toastr.warning('Seleziona un periodo dal calendario');
            return;
        }
        const $btn = $(this).prop('disabled', true);

        $(document).trigger('fetch', [{
            path: `/products/${PRODUCT_ID}/closed-periods`,
            method: 'post',
            data: { date_from: dateFrom, date_to: dateTo },
            then: function (period) {
                $('#closed-periods-empty').remove();
                var html =
                    '<div class="closed-period-item d-flex align-items-center gap-3 py-2 border-bottom" data-id="' + period.id + '">' +
                        '<i class="fa-regular fa-lock text-secondary"></i>' +
                        '<span class="flex-grow-1">' + period.date_from + ' – ' + period.date_to + '</span>' +
                        '<button type="button" class="bt-miticko btn-closed-period-delete" data-mode="small primary bt-m-text-only">' +
                            '<i class="fa-regular fa-trash icon"></i>' +
                        '</button>' +
                    '</div>';
                $('#closed-periods-list').append(html);
                $('#modal-closed-period').modal('hide');
                toastr.success('Periodo di chiusura creato');
                $btn.prop('disabled', false);
            },
            catch: function (err) {
                toastr.error((err && err.responseJSON && err.responseJSON.message) || 'Errore durante il salvataggio');
                $btn.prop('disabled', false);
            },
        }]);
    });

    $(document).on('click', '.btn-closed-period-delete', function () {
        var $item = $(this).closest('.closed-period-item');
        var id = $item.data('id');
        $(document).trigger('sweetConfirmTrigger', [{
            title: 'Elimina periodo',
            text: 'Vuoi eliminare questo periodo di chiusura?',
            callback: function () {
                $(document).trigger('fetch', [{
                    path: '/products/' + PRODUCT_ID + '/closed-periods/' + id,
                    method: 'delete',
                    then: function () {
                        $item.remove();
                        if ($('#closed-periods-list .closed-period-item').length === 0) {
                            $('#closed-periods-list').html('<p class="text-secondary small mb-0" id="closed-periods-empty">Nessun periodo di chiusura configurato.</p>');
                        }
                        toastr.success('Periodo eliminato');
                    },
                    catch: function () {
                        toastr.error('Errore durante l\'eliminazione');
                    },
                }]);
            },
        }]);
    });
})();
</script>
@endpush
