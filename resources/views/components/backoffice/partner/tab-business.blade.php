@props(['model'])

@php
    $billing = $model->billing ?? new \App\Models\PartnerBilling();
    $managedBy = $model->billing_managed_by ?? \App\Models\Partner::BILLING_MANAGED_BY_PARTNER;
    $mitickoLegalName = config('invoicing.miticko_legal_name', 'Colli Italiani SNC');
@endphp

<div class="tab-pane fade" id="partner-business-panel" role="tabpanel">
    <x-card title="Fatturazione" sub_title="I campi di fatturazione sono relativi al Partner" class="position-relative">
        <form id="form-partner-billing">
            <div class="row">
                <div class="col-12">
                    <div class="small fw-semibold mb-2">Gestione della fatturazione degli ordini</div>
                    <div class="d-flex flex-column gap-2">
                        <label class="d-inline-flex align-items-center gap-2">
                            <input type="radio" name="billing_managed_by" class="form-check-input billing-managed-by" value="miticko" @checked($managedBy === \App\Models\Partner::BILLING_MANAGED_BY_MITICKO)>
                            <span>gestita da Miticko ({{ $mitickoLegalName }})</span>
                        </label>
                        <label class="d-inline-flex align-items-center gap-2">
                            <input type="radio" name="billing_managed_by" class="form-check-input billing-managed-by" value="partner" @checked($managedBy === \App\Models\Partner::BILLING_MANAGED_BY_PARTNER)>
                            <span>gestita dal Partner (dati aziendali del partner)</span>
                        </label>
                    </div>
                </div>
            </div>

            <div id="partner-billing-data" class="mt-4 @if($managedBy === \App\Models\Partner::BILLING_MANAGED_BY_MITICKO) opacity-50 pe-none @endif">
                <div class="row">
                    <div class="col-12 mb-2">Dati fiscali</div>
                    <div class="col-12">
                        <x-input :model="$billing" name="legal_name" label="Ragione sociale" placeholder="Placeholder" />
                    </div>
                    <div class="col-12 col-sm-4 mt-3">
                        <x-input :model="$billing" name="vat_number" label="Partita IVA" placeholder="Placeholder" />
                    </div>
                    <div class="col-12 col-sm-4 mt-3">
                        <x-input :model="$billing" name="tax_code" label="Codice Fiscale" placeholder="Placeholder" />
                    </div>
                    <div class="col-12 col-sm-4 mt-3">
                        <x-input :model="$billing" name="sdi_code" label="Codice SDI" placeholder="Placeholder" />
                    </div>

                    <div class="col-12 mb-2 mt-4">Indirizzo</div>
                    <div class="col-12 col-sm-9">
                        <x-input :model="$billing" name="street_address" label="Indirizzo" placeholder="Placeholder" />
                    </div>
                    <div class="col-12 col-sm-3">
                        <x-input :model="$billing" name="postal_code" label="CAP" placeholder="Placeholder" />
                    </div>
                    <div class="col-12 col-sm-4 mt-3">
                        <x-input :model="$billing" name="city" label="Comune" placeholder="Placeholder" />
                    </div>
                    <div class="col-12 col-sm-4 mt-3">
                        <x-input :model="$billing" name="province" label="Provincia" placeholder="Placeholder" />
                    </div>
                    <div class="col-12 col-sm-4 mt-3">
                        <x-input :model="$billing" name="country" label="Paese" placeholder="Italia" />
                    </div>

                    <div class="col-12 mb-2 mt-4">Dati di contatto</div>
                    <div class="col-12 col-sm-4">
                        <x-input :model="$model" name="email_notify" label="Email principale" placeholder="Placeholder" />
                    </div>
                    <div class="col-12 col-sm-4">
                        <x-input :model="$billing" name="pec_email" label="PEC" placeholder="Placeholder" />
                    </div>
                    <div class="col-12 col-sm-4">
                        <x-input :model="$model" name="phone_number" label="Numero di telefono" placeholder="Placeholder" />
                    </div>
                </div>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>
</div>
