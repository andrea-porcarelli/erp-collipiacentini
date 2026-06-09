@extends('backoffice.layout', ['title' => 'Modifica partner', 'active' => $path])

@section('main-content')
    <div class="d-flex justify-content-between top-bar-page">
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button :href="route('partners.index')" class="btn-success" emphasis="outlined" leading="fa-arrow-left" />
            </div>
            <div>
                <x-breadcrumb :first="['Partner', 'partners.index']" :second="[$model->partner_name]" />
                <x-header-page :title="$model->partner_name" />
            </div>
        </div>
    </div>

    @php($isGod = Auth::user()->role === 'god')

    <div class="w-100 mt-spacing-2xl">
        {{-- Tabs Navigation --}}
        <div class="d-flex gap-2 product-tabs-scroll" id="partnerTabs" role="tablist">
            <x-chip label="Informazioni" :dataset="['tab-target' => '#partner-info-panel']" />
            <x-chip label="Dati aziendali" appearance="Resting" :dataset="['tab-target' => '#partner-business-panel']" />
            <x-chip label="Contatti" appearance="Resting" :dataset="['tab-target' => '#partner-contacts-panel']" />
            @if($isGod)
                <x-chip label="Privacy Policy"        appearance="Resting" :dataset="['tab-target' => '#partner-privacy-panel']" />
                <x-chip label="Cookie Policy"         appearance="Resting" :dataset="['tab-target' => '#partner-cookie-panel']" />
                <x-chip label="Termini e Condizioni"  appearance="Resting" :dataset="['tab-target' => '#partner-terms-panel']" />
            @endif
            <x-chip label="Consensi utente"           appearance="Resting" :dataset="['tab-target' => '#partner-consents-panel']" />
        </div>

        {{-- Tabs Content --}}
        <div class="tab-content mt-spacing-xl" id="partnerTabsContent">
            <x-backoffice.partner.tab-info     :model="$model" :hasOrders="$hasOrders" />
            <x-backoffice.partner.tab-business :model="$model" />
            <x-backoffice.partner.tab-policy :model="$model" type="contatti" field="contacts_content" label="Contatti" slug="contatti" panelId="partner-contacts-panel" />
            @if($isGod)
                <x-backoffice.partner.tab-policy :model="$model" type="privacy-policy"     field="privacy_policy"   label="Privacy Policy"        slug="privacy-policy"     panelId="partner-privacy-panel" />
                <x-backoffice.partner.tab-policy :model="$model" type="cookie-policy"      field="cookie_policy"    label="Cookie Policy"         slug="cookie-policy"      panelId="partner-cookie-panel" />
                <x-backoffice.partner.tab-policy :model="$model" type="termini-condizioni" field="terms_conditions" label="Termini e Condizioni"  slug="termini-condizioni" panelId="partner-terms-panel" />
            @endif
            <x-backoffice.partner.tab-consents :model="$model" />
        </div>
    </div>

    {{-- Modale traduzioni condivisa --}}
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
@endsection

@section('custom-css')
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
        #partnerTabsContent .tab-pane { display: none; }
        #partnerTabsContent .tab-pane.show.active { display: block; }

        /* Consensi: check "Obbligatorio" arancio (fill solid + check bianco) */
        .consent-required-wrap { cursor: pointer; user-select: none; }
        .consent-required-wrap.disabled { cursor: default; opacity: 0.75; }
        .consent-check-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            background: transparent;
            border: 1.5px solid #d1d5db;
            transition: background 0.15s, border-color 0.15s;
        }
        .consent-check-box.checked {
            background: #FF8A50;
            border-color: #FF8A50;
        }
        .consent-check-box i {
            color: #fff;
            font-size: 12px;
        }

        /* Consensi: stato disabilitato e drag */
        .consent-item.consent-disabled .card-miticko { opacity: 0.55; }
        .consent-item.consent-disabled .consent-handle,
        .consent-item.consent-disabled .consent-disabled-badge { opacity: 1; }
        .consent-item .sortable-ghost,
        #consents-list .sortable-ghost { opacity: 0.4; background: #f1f3f5; }

        /* Link visibili dentro al rich editor (CKEditor) */
        .ck.ck-content a,
        .ck.ck-content a:visited,
        .ck-editor__editable a,
        .ck-editor__editable a:visited {
            color: #0d6efd !important;
            text-decoration: underline !important;
            text-decoration-color: #0d6efd !important;
            text-decoration-thickness: 1px !important;
        }
        .ck.ck-content a:hover,
        .ck-editor__editable a:hover {
            color: #0a58ca !important;
            text-decoration: underline !important;
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

            // Tabs navigation
            const chips = document.querySelectorAll('#partnerTabs .chip-miticko');
            const panes = document.querySelectorAll('#partnerTabsContent .tab-pane');

            function showTab(target) {
                panes.forEach((pane) => {
                    pane.classList.remove('show', 'active');
                });
                const active = document.querySelector(target);
                if (active) active.classList.add('show', 'active');
            }

            chips.forEach((chip) => {
                chip.addEventListener('click', function () {
                    const target = this.getAttribute('data-tab-target');
                    if (!target) return;
                    showTab(target);
                    chips.forEach((c) => c.setAttribute('data-mode', 'chipAppearance-Resting'));
                    this.setAttribute('data-mode', 'chipAppearance-Active');
                });
            });
        });
    </script>
    <script src="{{ asset('backoffice/js/partners.js') }}?v=1.1" type="module"></script>
@endsection
