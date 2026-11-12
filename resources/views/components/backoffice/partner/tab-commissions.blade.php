@props(['model'])

<div class="tab-pane fade" id="partner-commissions-panel" role="tabpanel">
    <x-card title="Commissioni" class="position-relative">
        <form id="form-partner-commissions">
            <div class="row g-3">
                <div class="col-12 text-secondary small">A carico del cliente (prevendita)</div>

                <div class="col-12 col-md-4">
                    <x-input :model="$model" name="commission_presale_threshold_high" label="Per importi superiori a" leading="fa-greater-than" extra="EUR" />
                </div>
                <div class="col-12 col-md-4">
                    <x-input :model="$model" name="commission_presale_high" label="Prevendita applicata" extra="EUR" />
                </div>
                <div class="col-12 col-md-4">
                    <x-vat-select :model="$model" name="commission_presale_high_vat" label="IVA prevendita" />
                </div>

                <div class="col-12 col-md-4">
                    <x-input :model="$model" name="commission_presale_threshold_low" label="Per importi minori di" leading="fa-less-than" extra="EUR" />
                </div>
                <div class="col-12 col-md-4">
                    <x-input :model="$model" name="commission_presale_low" label="Prevendita applicata" extra="EUR" />
                </div>
                <div class="col-12 col-md-4">
                    <x-vat-select :model="$model" name="commission_presale_low_vat" label="IVA prevendita" />
                </div>

                <div class="col-12 text-secondary small mt-3">A carico del Partner (commissioni)</div>

                <div class="col-12 col-md-6">
                    <x-input :model="$model" name="commission_miticko_fixed" label="Commissione (fissa)" extra="EUR" />
                </div>
                <div class="col-12 col-md-6">
                    <x-vat-select :model="$model" name="commission_miticko_fixed_vat" label="IVA" />
                </div>

                <div class="col-12 col-md-6">
                    <x-input :model="$model" name="commission_miticko_variable" label="Commissione (percentuale)" extra="%" />
                </div>
                <div class="col-12 col-md-6">
                    <x-vat-select :model="$model" name="commission_miticko_variable_vat" label="IVA" />
                </div>

                <div class="col-12 col-md-6">
                    <x-input :model="$model" name="commission_payment" label="Commissione pagamento" extra="%" />
                </div>
                <div class="col-12 col-md-6">
                    <x-vat-select :model="$model" name="commission_payment_vat" label="IVA" />
                </div>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>
</div>
