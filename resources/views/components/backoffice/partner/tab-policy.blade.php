@props(['model', 'type', 'field', 'label', 'slug', 'panelId'])

@php($pageUrl = $model->pageUrl($slug))

<div class="tab-pane fade" id="{{ $panelId }}" role="tabpanel">
    <x-card :title="$label"  class="position-relative">
        <div class="row mb-spacing-m">
            <div class="col-12">
                <div class="partner-url-copy" data-url="{{ $pageUrl }}">
                    <x-input
                        :value="$pageUrl"
                        disabled
                        label="URL"
                        message="L'URL è assegnato automaticamente dal sistema e non può essere modificato"
                        icon="fa-regular fa-circle-info"
                        trailing="fa-copy"
                        leading="fa-link-simple"
                    />
                </div>
            </div>
        </div>

        <form id="form-partner-policy-{{ $type }}">
            <div class="legal-rich-editor position-relative"
                 data-legal-type="{{ $type }}"
                 data-legal-field="{{ $field }}"
                 data-it="{{ $model->contentField($field, 'it') ?? '' }}">
                <x-textarea
                    :value="$model->contentField($field, 'it') ?? ''"
                    :name="$field"
                    rows="12"
                    label="Testo"
                    maxlenght="$type === 'contatti' ? 600 : null"
                />
                <button type="button"
                        class="btn-legal-translations bt-miticko bt-m-light position-absolute"
                        data-legal-type="{{ $type }}"
                        data-mode="medium primary"
                        title="Traduci nelle altre lingue"
                        style="top:36px;right:8px;z-index:5;">
                    <i class="fa-regular fa-language icon"></i>
                </button>
            </div>

            @if($type === 'contatti')
                <div class="row mt-spacing-l">
                    <div class="col-12">
                        <x-input :model="$model" leading="fa-envelope" name="email_notify" label="Email notifiche" />
                    </div>
                    <div class="col-12">
                        <x-input :model="$model" leading="fa-phone" name="phone_number" label="Numero di telefono" />
                    </div>
                    <div class="col-12">
                        <x-input :model="$model" leading="fa-map-pin" name="structure_address" label="Indirizzo struttura" />
                    </div>
                </div>
            @endif
        </form>
        <div class="button-card-absolute">
            <x-button class="btn-save-card" label="Salva modifiche" leading="fa-save" status="Disabled" />
        </div>
    </x-card>
</div>
