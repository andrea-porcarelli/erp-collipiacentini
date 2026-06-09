@props(['model', 'hasOrders' => false])

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
                <div class="col-12 col-sm-4 mt-3">
                    <x-input :model="$model" name="email_notify" label="Email notifiche" />
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
                <x-button class="btn-logo-upload" label="Carica logo" leading="fa-upload" emphasis="Medium" size="Small" />
                <x-button class="btn-logo-delete" label="Rimuovi logo" leading="fa-trash" emphasis="MediumLow" status="Error" size="Small" :style="$model->logo ? '' : 'display:none'" />
            </div>
            <p class="text-secondary small mb-0">Formato consigliato orizzontale. Altezza massima di visualizzazione: 80px. Max 2MB.</p>
        </div>

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
                                  ['id' => 'none',                  'label' => 'Plugin esterno'],
                                  ['id' => 'whitelabel_domain',     'label' => 'Dominio dedicato'],
                                  ['id' => 'whitelabel_no_domain',  'label' => 'Miticko.com'],
                              ]"
                              :model="$model" />
                </div>
                <div class="col-12 col-sm-4" id="domain-name-container" style="{{ $model->sale_method !== 'none' ? '' : 'display:none' }}">
                    <x-input :model="$model" name="domain_name" label="Nome dominio" placeholder="es. www.esempio.it" />
                </div>
                <div class="col-12 col-sm-4 mt-3 mt-sm-0">
                    <x-select name="css_style" label="Stile CSS"
                              :options="collect(\App\Models\Partner::CSS_STYLES)->map(fn($s) => ['id' => $s, 'label' => $s])->all()"
                              :model="$model" />
                </div>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>

    <x-card title="Commissioni partner" class="mt-4 position-relative">
        <form id="form-partner-commissions">
            <div class="row">
                <div class="col-12 mb-2">A carico del cliente</div>
                <div class="col-12 col-sm-6">
                    <x-input :model="$model" name="commission_presale_threshold" label="Soglia prevendita (€)" />
                </div>
                <div class="col-12 col-sm-6"></div>
                <div class="col-12 col-sm-6">
                    <x-input :model="$model" name="commission_presale_low" label="Prevendita sotto soglia" />
                </div>
                <div class="col-12 col-sm-6">
                    <x-input :model="$model" name="commission_presale_high" label="Prevendita sopra soglia" />
                </div>
                <div class="col-12 mb-2 mt-4">A carico del Partner</div>
                <div class="col-12 col-sm-6">
                    <x-input :model="$model" name="commission_miticko_fixed" label="Commissione Miticko (fisso)" />
                </div>
                <div class="col-12 col-sm-6">
                    <x-input :model="$model" name="commission_miticko_variable" label="Commissione Miticko (variabile)" />
                </div>
                <div class="col-12 col-sm-6 mt-3">
                    <x-input :model="$model" name="commission_payment" label="Commissione di pagamento" />
                </div>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>

    <x-card title="Breve descrizione della tua attività"
            sub_title="Verrà mostrata nella home website del partner (testata) della sua pagina prodotto e ogni prodotto che ha in vendita."
            class="mt-4 position-relative">
        <form id="form-partner-description">
            <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                <div class="text-field-container position-relative">
                    <textarea id="partner-description-editor"
                              name="description_short"
                              rows="6"
                              data-it="{{ $model->contentField('description_short', 'it') ?? '' }}"></textarea>
                    <button type="button"
                            class="btn-description-translations bt-miticko bt-m-light position-absolute"
                            data-mode="medium primary"
                            title="Traduci nelle altre lingue"
                            style="top:8px;right:8px;z-index:5;">
                        <i class="fa-regular fa-language icon"></i>
                    </button>
                </div>
            </div>
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>


    <x-card title="Gestione utenti" class="mt-4 position-relative">
        <x-backoffice.partner.users :model="$model" />
        <div class="button-card-absolute">
            <x-button class="btn-user-add" status="Secondary" emphasis="MediumLow" label="Aggiungi account" leading="fa-plus" />
            <x-button class="btn-save-users" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>

    <x-card title="Elimina partner" class="mt-4 position-relative">
        <x-button status="Error" emphasis="MediumLow" label="Elimina partner" leading="fa-trash-can" class="btn-delete-partner" />
    </x-card>
</div>
