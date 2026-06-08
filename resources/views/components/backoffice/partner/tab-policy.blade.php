@props(['model', 'type', 'field', 'label', 'slug', 'panelId'])

<div class="tab-pane fade" id="{{ $panelId }}" role="tabpanel">
    <x-card :title="$label" sub_title="Documento legale pubblicato sul sito del partner" class="position-relative">
        <div class="d-flex align-items-center gap-2 mb-spacing-s">
            <span class="small text-secondary">URL della pagina (non modificabile):</span>
            <code class="small">/{{ $slug }}</code>
        </div>
        <form id="form-partner-policy-{{ $type }}">
            <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                <div class="text-field-container position-relative">
                    <textarea id="legal-editor-{{ $type }}"
                              name="{{ $field }}"
                              rows="12"
                              data-legal-type="{{ $type }}"
                              data-legal-field="{{ $field }}"
                              data-it="{{ $model->contentField($field, 'it') ?? '' }}"></textarea>
                    <button type="button"
                            class="btn-legal-translations bt-miticko bt-m-light position-absolute"
                            data-legal-type="{{ $type }}"
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
</div>
