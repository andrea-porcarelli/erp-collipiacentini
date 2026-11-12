@props(['model', 'hasOrders' => false])

@php
    $billing = $model->billing ?? new \App\Models\PartnerBilling();
    $managedBy = $model->billing_managed_by ?? \App\Models\Partner::BILLING_MANAGED_BY_PARTNER;
    $mitickoLegalName = config('invoicing.miticko_legal_name', 'Colli Italiani SNC');

    $countryOptions = collect([
        ['name' => 'Italia',          'flag' => '🇮🇹'],
        ['name' => 'Austria',         'flag' => '🇦🇹'],
        ['name' => 'Belgio',          'flag' => '🇧🇪'],
        ['name' => 'Bulgaria',        'flag' => '🇧🇬'],
        ['name' => 'Cipro',           'flag' => '🇨🇾'],
        ['name' => 'Croazia',         'flag' => '🇭🇷'],
        ['name' => 'Danimarca',       'flag' => '🇩🇰'],
        ['name' => 'Estonia',         'flag' => '🇪🇪'],
        ['name' => 'Finlandia',       'flag' => '🇫🇮'],
        ['name' => 'Francia',         'flag' => '🇫🇷'],
        ['name' => 'Germania',        'flag' => '🇩🇪'],
        ['name' => 'Grecia',          'flag' => '🇬🇷'],
        ['name' => 'Irlanda',         'flag' => '🇮🇪'],
        ['name' => 'Lettonia',        'flag' => '🇱🇻'],
        ['name' => 'Lituania',        'flag' => '🇱🇹'],
        ['name' => 'Lussemburgo',     'flag' => '🇱🇺'],
        ['name' => 'Malta',           'flag' => '🇲🇹'],
        ['name' => 'Paesi Bassi',     'flag' => '🇳🇱'],
        ['name' => 'Polonia',         'flag' => '🇵🇱'],
        ['name' => 'Portogallo',      'flag' => '🇵🇹'],
        ['name' => 'Regno Unito',     'flag' => '🇬🇧'],
        ['name' => 'Repubblica Ceca', 'flag' => '🇨🇿'],
        ['name' => 'Romania',         'flag' => '🇷🇴'],
        ['name' => 'San Marino',      'flag' => '🇸🇲'],
        ['name' => 'Slovacchia',      'flag' => '🇸🇰'],
        ['name' => 'Slovenia',        'flag' => '🇸🇮'],
        ['name' => 'Spagna',          'flag' => '🇪🇸'],
        ['name' => 'Stati Uniti',     'flag' => '🇺🇸'],
        ['name' => 'Svezia',          'flag' => '🇸🇪'],
        ['name' => 'Svizzera',        'flag' => '🇨🇭'],
        ['name' => 'Ungheria',        'flag' => '🇭🇺'],
    ])->map(fn ($c) => ['id' => $c['name'], 'label' => $c['flag'].' '.$c['name']])->all();
@endphp

<div class="tab-pane fade show active" id="partner-info-panel" role="tabpanel">
    <x-card title="Informazioni generali" sub_title="Dati principali del partner" class="position-relative">
        <form id="form-partner-info">
            <div class="row">
                <div class="col-12 col-sm-4">
                    <x-input :model="$model" name="partner_name" label="Nome partner" required />
                </div>
                <div class="col-12 col-sm-4">
                    <x-input :model="$model" name="partner_code" label="Codice partner" required
                             :disabled="$hasOrders"
                             :message="$hasOrders ? 'Non modificabile: esistono ordini registrati' : null" />
                </div>
                <div class="col-12 col-sm-4">
                    <x-select name="is_active" label="Stato partner" placeholder="Stato partner" required
                              :options="[['id' => 1, 'label' => 'Abilitato'],['id' => 0, 'label' => 'Non Abilitato']]"
                              icon="fa-regular fa-lock-open" :model="$model" />
                </div>
            </div>
        </form>

        <hr class="my-4">

        <div class="mb-2 fw-semibold">Logo partner</div>
        <div class="d-flex flex-column align-items-start gap-3">
            <div id="partner-logo-preview" class="d-flex align-items-center justify-content-center border rounded bg-light" style="min-width:200px;height:100px;padding:8px;overflow:hidden">
                @if($model->logo)
                    <img src="{{ asset('storage/' . $model->logo->file_path) }}" alt="Logo {{ $model->partner_name }}" style="max-height:84px;width:auto;object-fit:contain">
                @else
                    <span class="text-secondary small">Nessun logo</span>
                @endif
            </div>
            <div class="d-flex gap-2">
                <input type="file" id="partner-logo-input" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="d-none">
                <x-button class="btn-logo-upload" label="Carica logo" leading="fa-arrow-up-from-bracket" emphasis="Medium" size="Medium" status="Secondary" />
                <x-button class="btn-logo-delete" label="Rimuovi logo" leading="fa-trash-can " emphasis="Low" status="Neutral" size="Medium" :style="$model->logo ? '' : 'display:none'" />
            </div>
            <p class="text-secondary small mb-0">Formato consigliato orizzontale. Altezza massima di visualizzazione: 80px. Max 2MB.</p>
        </div>

        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>

    <x-card title="Testi pagina principale dell’E-commerce "
            sub_title="I testi verranno mostrati sulla pagina principale dell’ecommerce e serviranno anche per la SEO"
            class="mt-4 position-relative">
        <form id="form-partner-description">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-grow-1">
                            <x-input name="hero_title"
                                     label="Titolo E-commerce"
                                     placeholder="inserisci titolo"
                                     maxlength="70"
                                     message="(deve includere il nome della tua struttura)"
                                     :value="$model->contentField('hero_title', 'it') ?? ''" />
                        </div>
                        <x-icon-button
                            icon="fa-language"
                            class="btn-hero-translations flex-shrink-0"
                            data-field="hero_title"
                            appearance="Neutral"
                            emphasis="Medium"
                            title="Traduci nelle altre lingue"
                            style="margin-top: 26px;" />
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-grow-1">
                            <x-input name="hero_subtitle"
                                     label="Sottotitolo E-commerce"
                                     maxlength="70"
                                     message="(deve includere quello che offri sinteticamente)"
                                     placeholder="inserisci sottotitolo"
                                     :value="$model->contentField('hero_subtitle', 'it') ?? ''" />
                        </div>
                        <x-icon-button
                            icon="fa-language"
                            class="btn-hero-translations flex-shrink-0"
                            data-field="hero_subtitle"
                            appearance="Neutral"
                            emphasis="Medium"
                            title="Traduci nelle altre lingue"
                            style="margin-top: 26px;" />
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-grow-1">
                            <x-textarea
                                name="description_short"
                                size="textfieldSize-Medium"
                                id="partner-description-editor"
                                label="Descrizione attività in poche righe"
                                rows="6"
                                maxlength="300"
                                :value="$model->contentField('description_short', 'it') ?? ''"
                                data-it="{{ $model->contentField('description_short', 'it') ?? '' }}"
                            />
                        </div>
                        <x-icon-button
                            icon="fa-language"
                            class="btn-description-translations"
                            title="Traduci nelle altre lingue"
                            appearance="Neutral"
                            emphasis="Medium"
                            style="margin-top: 26px;" />
                    </div>
                </div>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>


    <x-card title="Gestione utenti" class="mt-4 position-relative">
        <x-backoffice.partner.users :model="$model" />

        <x-button class="btn-user-add" status="Secondary" emphasis="Medium" size="Medium" label="Aggiungi account" leading="fa-plus" />
        <div class="button-card-absolute">
            <x-button class="btn-save-users" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>

    <x-card title="Fatturazione" sub_title="I campi di fatturazione sono relativi al Partner" class="mt-4 position-relative">
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
                        <x-select :model="$billing" name="country" label="Paese" placeholder="Seleziona paese"
                                  :options="$countryOptions" />
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

    <x-card title="Configurazione vendita" class="mt-4 position-relative">
        <form id="form-partner-sale">
            <div class="row">
                <div class="col-12 col-sm-4">
                    <x-select name="sale_method" label="Metodo di vendita"
                              :options="[
                                  ['id' => 'none',                  'label' => 'Plugin Wordpress'],
                                  ['id' => 'whitelabel_domain',     'label' => 'Dominio dedicato'],
                                  ['id' => 'whitelabel_no_domain',  'label' => 'Miticko.com'],
                              ]"
                              :model="$model" />
                </div>
            </div>

            {{-- Plugin WordPress: token di accesso --}}
            <div id="sale-fields-plugin" class="mt-4" style="{{ $model->sale_method === 'none' ? '' : 'display:none' }}">
                <label>Token di accesso</label>
                <div class="d-flex align-items-center gap-3 mt-2">
                    <div class="flex-grow-1">
                        <x-input name="token" :value="$model->token" disabled />
                    </div>
                    <div>
                        <x-button label="Genera nuovo token" status="Neutral" emphasis="Low" size="Medium" leading="fa-rotate" class="btn-generate-partner-token" />
                    </div>
                </div>
                @if($model->token)
                    <x-supporting-text icon="fa-regular fa-circle-info" message="Attenzione: generare un nuovo token invaliderà quello attuale" />
                @else
                    <x-supporting-text icon="fa-regular fa-circle-info" message="Nessun token generato. Clicca il pulsante per crearne uno." />
                @endif
            </div>

            {{-- Dominio dedicato / Miticko.com: dominio o slug + stile + layout --}}
            <div id="sale-fields-whitelabel" class="row mt-3" style="{{ $model->sale_method !== 'none' ? '' : 'display:none' }}">
                <div class="col-12 col-sm-4" id="domain-name-container">
                    <x-input :model="$model" name="domain_name"
                             :label="$model->sale_method === 'whitelabel_no_domain' ? 'Link identificativo' : 'Nome dominio'"
                             :placeholder="$model->sale_method === 'whitelabel_no_domain' ? 'parte finale URL' : 'es. prenota.veleiaromana.it'" />
                </div>
                <div class="col-12 col-sm-4 mt-3 mt-sm-0">
                    <x-select name="css_style" label="Brand"
                              :options="collect(\App\Models\Partner::CSS_STYLES)->map(fn($s) => ['id' => $s, 'label' => $s])->all()"
                              :model="$model" />
                </div>
                <div class="col-12 col-sm-4 mt-3 mt-sm-0">
                    <x-select name="default_layout_mode" label="Layout di default"
                              :options="[
                                  ['id' => 'light', 'label' => 'Chiaro'],
                                  ['id' => 'dark',  'label' => 'Scuro'],
                              ]"
                              :model="$model" />
                </div>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>

    <x-card title="Elimina partner" class="mt-4 position-relative">
        <x-button status="Error" emphasis="MediumLow" label="Elimina partner" leading="fa-trash-can" class="btn-delete-partner" />
    </x-card>
</div>
