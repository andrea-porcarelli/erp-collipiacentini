@php
    use Illuminate\Support\Carbon;

    $months = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
        5 => 'Maggio',  6 => 'Giugno',   7 => 'Luglio', 8 => 'Agosto',
        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
    ];
    $monthOptions = collect($months)
        ->map(fn ($label, $id) => ['id' => $id, 'label' => $label])
        ->values()
        ->all();

    $currentYear = (int) Carbon::now()->year;
    $yearOptions = collect(range($currentYear, $currentYear - 5))
        ->map(fn ($y) => ['id' => $y, 'label' => (string) $y])
        ->all();

    $partnerOptions = collect($reportPartners)
        ->map(fn ($p) => ['id' => $p->id, 'label' => $p->partner_name])
        ->values()
        ->all();
@endphp

<div class="modal" tabindex="-1" id="modal-generate-report">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="width: 860px; max-width: 95vw;">
        <div class="modal-content modal-miticko modal-generate-report">
            <div class="modal-header" style="height: auto; padding-bottom: var(--spacing-m, 12px);">
                <div>
                    <h1 class="modal-title mb-1">Genera report</h1>
                    <small class="text-muted">genera e/o invia un report personalizzato</small>
                </div>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span class="fa-regular fa-times"></span>
                </button>
            </div>

            <div class="modal-body w-100">
                <form id="form-generate-report" class="switch-container">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Partner</label>
                        <x-select
                            name="report_partner_id"
                            :options="$partnerOptions"
                            :value="$reportCurrentPartnerId"
                        />
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Mese</label>
                            <x-select
                                name="report_month"
                                leading="fa-calendar"
                                :options="$monthOptions"
                            />
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Anno</label>
                            <x-select
                                name="report_year"
                                leading="fa-calendar"
                                :options="$yearOptions"
                            />
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-spacing-l">
                        <div>
                            <div class="fw-semibold">Invia report</div>
                            <div class="text-muted small">seleziona chi riceverà la copia in formato PDF via email</div>
                        </div>
                        <div class="switch-cell">
                            <x-switch name="report_send_master_toggle" />
                        </div>
                    </div>

                    <table class="table table-miticko align-middle w-100 mt-spacing-m mb-0">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Ruolo</th>
                                <th class="text-end" style="width: 140px;">Invia report</th>
                            </tr>
                        </thead>
                        <tbody id="generate-report-subscribers-tbody">
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    Seleziona un partner per vedere i destinatari configurati.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-end align-items-center gap-2 mt-spacing-s text-muted small">
                        <span>utenti per pagina</span>
                        <select class="input-miticko" style="width:70px;" disabled>
                            <option>10</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <x-button
                    class="btn-generate-report-send"
                    label="Invia report"
                    trailing="fa-paper-plane"
                    status="Disabled"
                    emphasis="High"
                />
                <x-button
                    class="btn-generate-report-download"
                    label="Scarica report"
                    trailing="fa-download"
                    status="Disabled"
                    emphasis="High"
                />
            </div>
        </div>
    </div>
</div>

<style>
    #modal-generate-report .modal-header .modal-title {
        font-size: var(--typography-title-size-large, 20px);
        line-height: 1.3;
    }
    #modal-generate-report .switch-cell {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 24px;
        vertical-align: middle;
    }
    #modal-generate-report .switch-cell .text-field { margin: 0; padding: 0; }
    #modal-generate-report .switch-cell .supporting-text { display: none; }
    #modal-generate-report table.table-miticko thead th {
        background: var(--Footer-Background-color, #FAFAFA);
        font-weight: 600;
        color: #666;
        border-bottom: none;
    }
    #modal-generate-report .modal-body { overflow-x: hidden; }
</style>
