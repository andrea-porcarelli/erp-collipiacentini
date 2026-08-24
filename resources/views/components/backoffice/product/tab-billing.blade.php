@props(['model'])

<div class="tab-pane fade" id="billing-panel" role="tabpanel" aria-labelledby="billing-tab">
    <div class="row">
        <div class="col-12">
            <x-card title="Voci di fatturazione" sub_title="Seleziona quali voci includere automaticamente nella fattura degli ordini di questo prodotto. Gli importi vengono presi dalla configurazione partner." class="position-relative">
                <form id="form-info-billing">
                    <div class="row switch-container">
                        <div class="col-12 col-lg-8 col-xl-7">
                            <x-switch
                                name="bill_ticket_base"
                                class="switch-bill-ticket-base"
                                :checked="(bool) $model->bill_ticket_base"
                                label="Biglietto base"
                                message="Se attivo, le commissioni Miticko e bancarie risultano già comprese nel biglietto e vengono forzate."
                            />
                        </div>
                        <div class="col-12 col-lg-8 col-xl-7 mt-4">
                            <x-switch
                                name="bill_presale"
                                class="switch-bill-presale"
                                :checked="(bool) $model->bill_presale"
                                label="Prevendita"
                                message="Importo derivato dalla soglia e dalle fasce commissione presale del partner."
                            />
                        </div>
                        <div class="col-12 col-lg-8 col-xl-7 mt-4">
                            <x-switch
                                name="bill_miticko_commission"
                                class="switch-bill-miticko-commission"
                                :checked="(bool) $model->bill_miticko_commission"
                                label="Commissione Miticko"
                                message="Componente fissa + variabile dalla configurazione partner."
                            />
                        </div>
                        <div class="col-12 col-lg-8 col-xl-7 mt-4">
                            <x-switch
                                name="bill_bank_commission"
                                class="switch-bill-bank-commission"
                                :checked="(bool) $model->bill_bank_commission"
                                label="Commissioni bancarie"
                                message="Percentuale di gateway pagamento dalla configurazione partner."
                            />
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
