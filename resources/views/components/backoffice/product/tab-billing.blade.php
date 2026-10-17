@props(['model'])

<div class="tab-pane fade" id="billing-panel" role="tabpanel" aria-labelledby="billing-tab">
    <div class="row">
        <div class="col-12">
            <x-card title="Fatturazione" sub_title="modifica le impostazioni di default di fatturazione per questo prodotto" class="position-relative">
                <form id="form-info-billing">
                    <div class="row">
                        <div class="col-12 col-lg-6 col-xl-5">
                            <x-select
                                name="invoice_required"
                                label="Fattura"
                                :value="(bool) $model->invoice_required ? '1' : '0'"
                                :options="[
                                    ['id' => '1', 'label' => 'Necessaria'],
                                    ['id' => '0', 'label' => 'Non necessaria'],
                                ]"
                            />
                        </div>
                    </div>

                    <div id="billing-flags-wrapper" class="mt-4 @if(! $model->invoice_required) d-none @endif">
                        <div class="small fw-semibold mb-2">Valori di default includere nelle fatture</div>
                        <div class="d-flex flex-column gap-2">
                            <label class="d-inline-flex align-items-center gap-2">
                                <input type="hidden" name="bill_ticket_base" value="0">
                                <input type="checkbox" class="form-check-input bill-flag" name="bill_ticket_base" id="bill_ticket_base" value="1" @checked((bool) $model->bill_ticket_base)>
                                <span>Biglietto base (non include le eventuali commissioni miticko)</span>
                            </label>
                            <label class="d-inline-flex align-items-center gap-2">
                                <input type="hidden" name="bill_miticko_commission" value="0">
                                <input type="checkbox" class="form-check-input bill-flag" name="bill_miticko_commission" id="bill_miticko_commission" value="1" @checked((bool) $model->bill_miticko_commission)>
                                <span>Commissioni Miticko</span>
                            </label>
                            <label class="d-inline-flex align-items-center gap-2">
                                <input type="hidden" name="bill_presale" value="0">
                                <input type="checkbox" class="form-check-input bill-flag" name="bill_presale" id="bill_presale" value="1" @checked((bool) $model->bill_presale)>
                                <span>Prevendita Miticko</span>
                            </label>
                            <label class="d-inline-flex align-items-center gap-2">
                                <input type="hidden" name="bill_bank_commission" value="0">
                                <input type="checkbox" class="form-check-input bill-flag" name="bill_bank_commission" id="bill_bank_commission" value="1" @checked((bool) $model->bill_bank_commission)>
                                <span>Commissioni gateway di pagamento</span>
                            </label>
                        </div>
                    </div>
                </form>
                <div class="button-card-absolute">
                    <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                </div>
            </x-card>
        </div>
    </div>
</div>
