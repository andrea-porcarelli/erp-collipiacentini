@props(['model'])

@php($billing = $model->billing ?? new \App\Models\PartnerBilling())

<div class="tab-pane fade" id="partner-business-panel" role="tabpanel">
    <x-card title="Dati di fatturazione" sub_title="Anagrafica, sede legale e dati bancari" class="position-relative">
        <form id="form-partner-billing">
            <div class="row">
                <div class="col-12 mb-2">Anagrafica</div>
                <div class="col-12 col-sm-6">
                    <x-input :model="$billing" name="legal_name" label="Ragione sociale" />
                </div>
                <div class="col-12 col-sm-3">
                    <x-input :model="$billing" name="vat_number" label="Partita IVA" />
                </div>
                <div class="col-12 col-sm-3">
                    <x-input :model="$billing" name="tax_code" label="Codice fiscale" />
                </div>

                <div class="col-12 mb-2 mt-4">Sede legale</div>
                <div class="col-12 col-sm-8">
                    <x-input :model="$billing" name="street_address" label="Indirizzo" />
                </div>
                <div class="col-12 col-sm-2">
                    <x-input :model="$billing" name="postal_code" label="CAP" />
                </div>
                <div class="col-12 col-sm-2">
                    <x-input :model="$billing" name="province" label="Prov." />
                </div>
                <div class="col-12 col-sm-6 mt-3">
                    <x-input :model="$billing" name="city" label="Città" />
                </div>
                <div class="col-12 col-sm-6 mt-3">
                    <x-input :model="$billing" name="country" label="Nazione (ISO 3166-1 alpha-2)" />
                </div>

                <div class="col-12 mb-2 mt-4">Fatturazione elettronica</div>
                <div class="col-12 col-sm-6">
                    <x-input :model="$billing" name="pec_email" label="PEC" />
                </div>
                <div class="col-12 col-sm-6">
                    <x-input :model="$billing" name="sdi_code" label="Codice SDI" />
                </div>

                <div class="col-12 mb-2 mt-4">Dati bancari</div>
                <div class="col-12 col-sm-8">
                    <x-input :model="$billing" name="iban" label="IBAN" />
                </div>
                <div class="col-12 col-sm-4">
                    <x-input :model="$billing" name="tax_regime" label="Regime fiscale" />
                </div>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>
</div>
