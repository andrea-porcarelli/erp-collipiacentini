@props(['model'])

<div class="tab-pane fade" id="schedule-panel" role="tabpanel" aria-labelledby="schedule-tab">
    <div class="row">
        <div class="col-12">
            <x-card title="Date e orari" sub_title="definisci i giorni e gli orari di apertura standard">
                @php
                $days = [1 => 'Lunedì', 2 => 'Martedì', 3 => 'Mercoledì', 4 => 'Giovedì', 5 => 'Venerdì', 6 => 'Sabato', 7 => 'Domenica'];
                @endphp
                @foreach($days as $dayIndex => $dayName)
                    {{-- Riga giorno --}}
                    <div class="col-12 schedule-panel-day" data-day="{{ $dayIndex }}">
                        <h3>{{ $dayName }}</h3>
                        <div class="day-status">
                            <x-dropdown
                                name="is_open[{{ $dayIndex }}]"
                                :options="[['id' => 1, 'label' => 'Aperto'],['id' => 0, 'label' => 'Chiuso']]"
                                leading="fa-lock"
                                class="schedule-is-open"
                                :dataset="['day' => $dayIndex]"
                            />
                        </div>
                    </div>

                    {{-- Lista slot (visibile solo se Aperto) --}}
                    <div class="day-slots-container" data-day="{{ $dayIndex }}" style="display:none">
                        <div class="slot-list mt-spacing-xl" id="slots-{{ $dayIndex }}">
                            <div class="slot-loading text-secondary small py-2">
                                <i class="fa-regular fa-spinner fa-spin me-1"></i> Caricamento...
                            </div>
                        </div>
                        <div class="mt-spacing-m">
                            <x-button class="btn-add-slot" :dataset="['day' => $dayIndex, 'product' => $model->id]" label="Aggiungi orario" emphasis="High" leading="fa-plus"   />
                        </div>
                    </div>

                    <div class="col-12 mt-spacing-2xl mb-spacing-2xl">
                        <hr />
                    </div>
                @endforeach
            </x-card>
        </div>
    </div>
</div>

@once
<style>
    .schedule-panel-day {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .schedule-panel-day h3 {
        margin: 0;
        color: var(--text-main, #0D0D0D);
        font-family: var(--font-font-1, "DM Sans"), sans-serif;
        font-size: var(--typography-title-size-large, 28px);
        font-style: normal;
        font-weight: var(--typography-title-weight-large, 600);
        line-height: var(--typography-title-line-height-large, 34px);
    }
</style>
@push('scripts')
    <script>
        (function () {
            const PRODUCT_ID = window.PRODUCT_ID;

            // Toggle expand/collapse slot
            $(document).on('click', '.btn-slot-toggle', function () {
                const item = $(this).closest('.slot-item');
                const body = item.find('.slot-item-body');
                const icon = $(this).find('i');
                const open = body.is(':visible');
                body.toggle(!open);
                icon[0].className = open ? 'fa-regular fa-chevron-down' : 'fa-regular fa-chevron-up';
            });

            // Aggiorna label al cambio orario
            $(document).on('input', '.slot-time-input', function () {
                const item = $(this).closest('.slot-item');
                item.find('.slot-time-label').text(this.value || '—');
                item.find('.slot-badge-modified').css('display', 'flex');
            });

            // Salva slot
            $(document).on('click', '.btn-slot-save', function () {
                const item   = $(this).closest('.slot-item');
                const slotId = item.data('slot-id');
                const time   = item.find('.slot-time-input').val();
                if (!time) { toastr.warning('Inserisci un orario valido'); return; }

                const btn = this;
                btn.disabled = true;
                $(document).trigger('fetch', [{
                    path: `/products/${PRODUCT_ID}/schedule/${slotId}`,
                    method: 'put',
                    data: { time },
                    then: (res) => {
                        item.find('.slot-time-label').text(res.time.substring(0, 5));
                        item.find('.slot-badge-modified').hide();
                        item.find('.slot-item-body').hide();
                        item.find('.btn-slot-toggle i')[0].className = 'fa-regular fa-chevron-down';
                        toastr.success('Orario aggiornato');
                        btn.disabled = false;
                    },
                    catch: () => {
                        toastr.error('Errore durante il salvataggio');
                        btn.disabled = false;
                    },
                }]);
            });

            // Elimina slot
            $(document).on('click', '.btn-slot-delete', function () {
                const item   = $(this).closest('.slot-item');
                const slotId = item.data('slot-id');
                $(document).trigger('sweetConfirmTrigger', [{
                    text: 'Vuoi eliminare questo slot orario?',
                    title: 'Elimina slot',
                    callback: () => {
                        $(document).trigger('fetch', [{
                            path: `/products/${PRODUCT_ID}/schedule/${slotId}`,
                            method: 'delete',
                            then: () => {
                                item.remove();
                                toastr.success('Slot eliminato');
                            },
                            catch: () => toastr.error('Errore durante l\'eliminazione'),
                        }]);
                    },
                }]);
            });

            function loadSlots(dayIndex) {
                const list = document.getElementById('slots-' + dayIndex);
                if (!list) return;

                list.innerHTML = '<div class="slot-loading text-secondary small py-2"><i class="fa-regular fa-spinner fa-spin me-1"></i> Caricamento...</div>';
                $.ajax({
                    url: `/products/${PRODUCT_ID}/schedule/${dayIndex}`,
                    method: 'get',
                    dataType: 'json',
                    success: (res) => {
                        list.innerHTML = res.response;
                    },
                    error: () => {
                        list.innerHTML = '<p class="text-danger small mb-0">Errore nel caricamento degli orari.</p>';
                    },
                });
            }

            // Toggle Aperto/Chiuso
            document.querySelectorAll('.schedule-is-open').forEach(function (select) {
                select.addEventListener('change', function () {
                    const dayIndex = this.closest('[data-day]')?.dataset.day;
                    if (!dayIndex) return;
                    const container = document.querySelector(`.day-slots-container[data-day="${dayIndex}"]`);
                    if (!container) return;

                    if (parseInt(this.value) === 1) {
                        container.style.display = 'block';
                        loadSlots(dayIndex);
                    } else {
                        container.style.display = 'none';
                    }
                });
            });

            // Aggiungi orario — apre la modale
            let _pendingDay = null;

            $(document).on('click', '.btn-add-slot', function () {
                _pendingDay = $(this).data('day');
                $('#slot-hour').val(9);
                $('#slot-minute').val('00');
                $('#modal-add-slot').modal('show');
            });

            // Confirm dalla modale
            $(document).on('click', '#btn-confirm-add-slot', function () {
                if (!_pendingDay) return;

                const hour   = String($('#slot-hour').val()).padStart(2, '0');
                const minute = $('#slot-minute').val();
                const time   = `${hour}:${minute}`;
                const dayIndex = _pendingDay;
                const $btn = $(this);

                $btn.prop('disabled', true);
                $(document).trigger('fetch', [{
                    path: `/products/${PRODUCT_ID}/schedule`,
                    method: 'post',
                    data: { day_of_week: parseInt(dayIndex), time },
                    then: (slot) => {
                        const list = document.getElementById('slots-' + dayIndex);
                        const empty = list.querySelector('p.text-secondary');
                        if (empty) empty.remove();
                        loadSlots(dayIndex);
                        $('#modal-add-slot').modal('hide');
                        toastr.success('Orario aggiunto');
                        $btn.prop('disabled', false);
                    },
                    catch: () => {
                        toastr.error('Errore durante l\'aggiunta dello slot');
                        $btn.prop('disabled', false);
                    },
                }]);
            });

            @foreach($days as $dayIndex => $dayName)
                @if(in_array($dayIndex, $model->availability_days))
                    (function () {
                        const sel = document.querySelector(`.schedule-panel-day[data-day="{{ $dayIndex }}"] select`);
                        if (!sel) return;
                        const icon = document.querySelector(`.schedule-panel-day[data-day="{{ $dayIndex }}"] .icon`);
                        if (icon) { icon.classList.remove('fa-lock'); icon.classList.add('fa-lock-open'); }
                        sel.value = '1';
                        sel.dispatchEvent(new Event('change'));
                    })();
                @endif
            @endforeach
        })();
    </script>

@endpush
@endonce
