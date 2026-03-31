@props(['model', 'languages' => []])

<div class="tab-pane fade" id="media-panel" role="tabpanel" aria-labelledby="media-tab">
    <div class="row">
        <div class="col-12">

            {{-- Card Foto --}}
            <x-card title="Inserisci le immagini del prodotto"
                    sub_title="verranno visualizzate nella galleria prodotto, puoi sceglierne fino a 5"
                    class="position-relative">

                <div class="button-card-absolute d-flex gap-2 align-items-center">
                    <input type="file" id="media-file-input" accept="image/jpeg,image/png,image/webp" style="display:none">
                    <x-button label="Aggiungi immagine" emphasis="High" trailing="fa-plus" class="btn-add-media" />
                </div>

                <div id="media-list">
                    @forelse($model->gallery as $media)
                        <div class="media-item d-flex align-items-center gap-3 py-2 border-bottom" data-id="{{ $media->id }}">
                            <span class="drag-handle text-secondary" style="cursor:grab;font-size:18px;line-height:1">⠿</span>
                            <img src="{{ asset('storage/' . $media->file_path) }}"
                                 alt="{{ $media->file_name }}"
                                 style="width:56px;height:56px;object-fit:cover;border-radius:6px;flex-shrink:0">
                            <span class="flex-grow-1 small text-truncate">{{ $media->file_name }}</span>
                            <button type="button" class="bt-miticko btn-media-delete" data-mode="small primary bt-m-text-only">
                                <i class="fa-regular fa-trash icon"></i>
                            </button>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0" id="media-empty">Nessuna immagine aggiunta.</p>
                    @endforelse
                </div>
            </x-card>

            {{-- Card Descrizione --}}
            <x-card title="Descrizione completa del prodotto"
                    sub_title="inserisci la descrizione completa del prodotto con più dettagli possibili"
                    class="position-relative mt-4">

                <div class="button-card-absolute">
                    <x-button label="Salva modifiche" status="Disabled" leading="fa-floppy-disk" class="btn-save-long-description" />
                </div>

                @if(count($languages) > 0)
                    <div class="col-12 col-sm-3">
                        <div class="mb-spacing-m">
                            <x-select name="partner_idlong-desc-language-select" leading="fa-language" placeholder="Seleziona" :options="$languages" />
                        </div>
                    </div>
                @endif

                <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                    <div class="text-field-container">
                        <textarea id="long-description-editor" name="long_description" rows="8" data-it="{{ $model->contentField('long_description') ?? '' }}"></textarea>
                    </div>
                </div>
                <div class="text-end mt-1">
                    <span class="small text-secondary"><span id="long-desc-count">0</span> caratteri</span>
                </div>
            </x-card>

        </div>
    </div>
</div>
