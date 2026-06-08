@extends('backoffice.layout', ['title' => 'Modifica partner', 'active' => $path])

@section('main-content')
    <div class="d-flex justify-content-between top-bar-page">
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button  class="btn-success" emphasis="outlined"  leading="fa-arrow-left" />
            </div>
            <div>
                <x-breadcrumb :first="['Partner', 'partners.index']" :second="[$model->partner_name]" />
                <x-header-page :title="$model->partner_name" />
            </div>
        </div>
    </div>
    <div class="w-100">
        <div class="row">
            <div class="col-12">
                <x-card title="Stato partner" class="position-relative">
                    <div class="row mt-3">
                        <div class="col-12 col-md-4">
                            <form id="form-partner-status">
                                <x-select name="is_active" label="Stato partner" placeholder="Stato partner" required :options="[['id' => 1, 'label' => 'Abilitato'],['id' => 0, 'label' => 'Non Abilitato']]" icon="fa-regular fa-lock-open" :model="$model" />
                            </form>
                        </div>
                        <div class="col-12 col-md-8 mt-3 mt-md-0">
                            <div class="text-field" data-mode="textfieldSize-Medium">
                                <label>Logo partner</label>
                                <div class="mt-spacing-s mb-spacing-s">
                                    <div id="partner-logo-preview" class="d-flex align-items-center justify-content-center border rounded bg-light" style="min-width:160px;height:88px;padding:4px;overflow:hidden">
                                        @if($model->logo)
                                            <img src="{{ asset('storage/' . $model->logo->file_path) }}" alt="Logo {{ $model->partner_name }}" style="max-height:80px;width:auto;object-fit:contain">
                                        @else
                                            <span class="text-secondary small">Nessun logo</span>
                                        @endif
                                    </div>
                                    <div class="d-flex mt-spacing-m gap-2">
                                        <input type="file" id="partner-logo-input" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="d-none">
                                        <x-button class="btn-logo-upload" label="Carica logo" leading="fa-upload" emphasis="Medium" size="Small" />
                                        <x-button class="btn-logo-delete" label="Rimuovi logo" leading="fa-trash" emphasis="MediumLow" status="Error" size="Small" :style="$model->logo ? '' : 'display:none'" />
                                    </div>
                                    <p class="text-secondary small mt-2 mb-0">Formato consigliato orizzontale. Altezza massima di visualizzazione: 80px. Max 2MB.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="button-card-absolute">
                        <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                    </div>
                </x-card>

                <x-card title="Informazioni partner" sub_title="Dati principali del partner" class="mt-4 position-relative">
                    <form id="form-partner-info">
                        <div class="row">
                            <div class="col-12 col-sm-4">
                                <x-input :model="$model" name="partner_name" label="Nome partner" required />
                            </div>
                            <div class="col-12 col-sm-4">
                                <x-input
                                    :model="$model"
                                    name="partner_code"
                                    label="Codice partner"
                                    required
                                    :disabled="$hasOrders"
                                    :message="$hasOrders ? 'Non modificabile: esistono ordini registrati' : null"
                                />
                            </div>
                            <div class="col-12 col-sm-4">
                                <x-input :model="$model" name="email_notify" label="Email notifiche" />
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 col-sm-4">
                                <x-select
                                    name="sale_method"
                                    label="Metodo di vendita"
                                    :options="[
                                        ['id' => 'none',              'label' => 'Plugin esterno'],
                                        ['id' => 'whitelabel_domain', 'label' => 'Dominio dedicato'],
                                        ['id' => 'whitelabel_no_domain', 'label' => 'Miticko.com'],
                                    ]"
                                    :model="$model"
                                />
                            </div>
                            <div class="col-12 col-sm-4" id="domain-name-container" style="{{ $model->sale_method !== 'none' ? '' : 'display:none' }}">
                                <x-input :model="$model" name="domain_name" label="Nome dominio" placeholder="es. www.esempio.it" />
                            </div>
                            <div class="col-12 col-sm-4 mt-3 mt-sm-0">
                                <x-select
                                    name="css_style"
                                    label="Stile CSS"
                                    :options="collect(\App\Models\Partner::CSS_STYLES)->map(fn($s) => ['id' => $s, 'label' => $s])->all()"
                                    :model="$model"
                                />
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
                            <div class="col-12 mb-2">
                                A carico del cliente
                            </div>
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
                            <div class="col-12 mb-2 mt-4">
                                A carico del Partner
                            </div>
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

                @php($billing = $model->billing ?? new \App\Models\PartnerBilling())
                <x-card title="Dati di fatturazione" sub_title="Anagrafica, sede legale e dati bancari" class="mt-4 position-relative">
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

                <x-card title="Gestione account" class="mt-4 position-relative">
                    <x-backoffice.partner.users :model="$model" />
                    <div class="button-card-absolute">
                        <x-button class="btn-user-add"  status="Secondary" emphasis="MediumLow"  label="Aggiungi account" leading="fa-plus" />
                        <x-button class="btn-save-users" label="Salva modifiche" leading="fa-save" status="Disabled" />
                    </div>
                </x-card>

                @php($legalTabs = ['privacy-policy' => ['label' => 'Privacy Policy', 'icon' => 'fa-shield-check', 'field' => 'privacy_policy'], 'cookie-policy' => ['label' => 'Cookie Policy', 'icon' => 'fa-cookie-bite', 'field' => 'cookie_policy'], 'termini-condizioni' => ['label' => 'Termini e Condizioni', 'icon' => 'fa-file-contract', 'field' => 'terms_conditions']])
                @if(Auth::user()->role === 'god')
                    <x-card title="Politiche e Condizioni" sub_title="Documenti legali pubblicati sul sito del partner" class="mt-4 position-relative">
                        <ul class="nav nav-tabs entity-tabs" role="tablist">
                            @foreach($legalTabs as $slug => $tab)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" type="button"
                                            data-bs-toggle="tab" data-bs-target="#tab-legal-{{ $slug }}"
                                            role="tab" aria-controls="tab-legal-{{ $slug }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                        <i class="fa-regular {{ $tab['icon'] }} me-1"></i> {{ $tab['label'] }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            <form id="form-partner-policies">
                                @foreach($legalTabs as $slug => $tab)
                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                         id="tab-legal-{{ $slug }}" role="tabpanel">
                                        <div class="d-flex align-items-center gap-2 mb-spacing-s">
                                            <span class="small text-secondary">URL della pagina (non modificabile):</span>
                                            <code class="small">/{{ $slug }}</code>
                                        </div>
                                        <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                                            <div class="text-field-container position-relative">
                                                <textarea id="legal-editor-{{ $slug }}"
                                                          name="{{ $tab['field'] }}"
                                                          rows="10"
                                                          data-legal-type="{{ $slug }}"
                                                          data-legal-field="{{ $tab['field'] }}"
                                                          data-it="{{ $model->contentField($tab['field'], 'it') ?? '' }}"></textarea>
                                                <button type="button"
                                                        class="btn-legal-translations bt-miticko bt-m-light position-absolute"
                                                        data-legal-type="{{ $slug }}"
                                                        data-mode="medium primary"
                                                        title="Traduci nelle altre lingue"
                                                        style="top:8px;right:8px;z-index:5;">
                                                    <i class="fa-regular fa-language icon"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </form>
                        </div>
                        <div class="button-card-absolute">
                            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
                        </div>
                    </x-card>

                    {{-- Modale traduzioni dei documenti legali --}}
                    <div class="modal" tabindex="-1" id="modal-translations">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content modal-miticko">
                                <div class="modal-header">
                                    <h1 class="modal-title">Traduci</h1>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <span class="fa-regular fa-times"></span>
                                    </button>
                                </div>
                                <div class="modal-body w-100" id="modal-trans-body">
                                    <div class="text-center py-3">
                                        <i class="fa-regular fa-spinner fa-spin"></i>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <x-button size="Small " emphasis="Low" label="annulla" :dataset="['bs-dismiss' => 'modal']" />
                                    <x-button size="Small " emphasis="High" class="btn-save-translations" label="Salva" />
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <x-card title="Elimina partner" class="mt-4 position-relative">
                    <x-button status="Error" emphasis="MediumLow" label="Elimina partner" leading="fa-trash-can" class="btn-delete-partner" />
                </x-card>
            </div>
        </div>
    </div>
@endsection

@section('custom-css')
@if(Auth::user()->role === 'god')
    <link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css" />
    <script type="importmap">
    {
        "imports": {
            "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.js",
            "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.3.1/"
        }
    }
    </script>
    <style>
        .ck.ck-editor__top .ck-sticky-panel .ck-sticky-panel__content { border: none !important; }
        .text-field .text-field-container:has(.ck-editor) { display: block; padding: 0; overflow: hidden; }
        .text-field .text-field-container .ck.ck-editor { width: 100%; border: none; box-shadow: none; }
        .text-field .text-field-container .ck.ck-toolbar {
            border: none !important;
            border-bottom: 1px solid var(--text-field-empty-border, #E6E6E6) !important;
            border-radius: 0 !important;
            background: #f9f9f9;
            box-shadow: none !important;
        }
        .text-field .text-field-container .ck.ck-editor__editable {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            outline: none !important;
            background: var(--text-field-empty-background, #FFF);
            color: var(--text-main);
            font-family: var(--font-font-1), serif;
            font-size: var(--typography-body-size-medium);
            font-weight: var(--typography-body-weight-medium);
            line-height: var(--typography-body-lineheight-medium);
            padding: var(--text-field-paddingvertical) var(--text-field-paddinghorizontal);
            min-height: 180px;
        }
    </style>
@endif
<style>
    .entity-tabs {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 24px;
        gap: 8px;
    }

    .entity-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: #6c757d;
        font-weight: 500;
        padding: 12px 20px;
        transition: all 0.2s ease;
    }

    .entity-tabs .nav-link:hover {
        border-bottom-color: #dee2e6;
        color: #495057;
    }

    .entity-tabs .nav-link.active {
        border-bottom-color: var(--bs-primary, #0d6efd);
        color: var(--bs-primary, #0d6efd);
        background-color: transparent;
    }

    .entity-tabs .nav-link i {
        font-size: 14px;
    }

    .tab-content {
        padding-top: 8px;
    }
</style>
@endsection

@section('custom-script')
    <script>
        window.PARTNER_ID = {{ $model->id }};

        $(function () {
            @if($model->sale_method === 'whitelabel_no_domain')
                $('#domain-name-container label').first().text('Slug identificativo partner');
            @endif

            $(document).on('change', 'select[name="sale_method"]', function () {
                const val = $(this).val();
                $('#domain-name-container').toggle(val !== 'none');
                $('#domain-name-container label').first().text(
                    val === 'whitelabel_no_domain' ? 'Slug identificativo partner' : 'Nome dominio'
                );
            });
        });
    </script>
    <script src="{{ asset('backoffice/js/partners.js') }}?v=1.0" type="module"></script>
@endsection
