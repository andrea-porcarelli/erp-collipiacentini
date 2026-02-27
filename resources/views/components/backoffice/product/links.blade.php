@props(['model', 'languages'])

{{-- Lista link --}}
<div id="links-list" class="mb-4">
    @forelse($model->links()->with('language')->get() as $link)
        <div class="link-item" data-id="{{ $link->id }}">
            {{-- Riga display --}}
            <div class="link-display d-flex align-items-center gap-3 py-2 border-bottom">
                <span class="badge bg-secondary mt-1" style="font-size:11px;white-space:nowrap">{{ $link->language?->label ?? '—' }}</span>
                <span class="link-col-label fw-semibold flex-grow-1">{{ $link->label }}</span>
                <span class="link-col-url text-secondary small text-truncate" style="max-width: 220px">{{ $link->link }}</span>
                <div class="d-flex gap-2">
                    <x-button emphasis="outlined" status="secondary" size="small" leading="fa-pen" class="btn-link-edit" />
                    <x-button emphasis="outlined" status="danger" size="small" leading="fa-trash" class="btn-link-delete" />
                </div>
            </div>
            {{-- Riga edit (nascosta) --}}
            <div class="link-edit d-none py-2 border-bottom">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-sm-3">
                        <x-select name="language_id" label="Lingua" :options="$languages" :value="$link->language_id" />
                    </div>
                    <div class="col-12 col-sm-3">
                        <x-input name="label" label="Label" :value="$link->label" />
                    </div>
                    <div class="col-12 col-sm-4">
                        <x-input name="link" label="URL" :value="$link->link" />
                    </div>
                    <div class="col-12 col-sm-2 d-flex gap-2">
                        <x-button emphasis="primary" status="success" size="small" leading="fa-check" class="btn-link-save" />
                        <x-button emphasis="outlined" status="secondary" size="small" leading="fa-xmark" class="btn-link-cancel" />
                    </div>
                </div>
            </div>
        </div>
    @empty
        <p class="text-secondary small mb-0" id="links-empty">Nessun link aggiunto.</p>
    @endforelse
</div>

{{-- Form nuovo link --}}
<div class="border-top pt-3">
    <p class="fw-semibold small mb-3">Aggiungi link</p>
    <form id="form-link-new">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-3">
                <x-select name="language_id" label="Lingua" :options="$languages" />
            </div>
            <div class="col-12 col-sm-3">
                <x-input name="label" label="Label" placeholder="es. Prenota ora" />
            </div>
            <div class="col-12 col-sm-4">
                <x-input name="link" label="URL" placeholder="https://..." />
            </div>
            <div class="col-12 col-sm-2">
                <x-button id="btn-link-add" emphasis="primary" status="success" size="small" leading="fa-plus" label="Aggiungi" />
            </div>
        </div>
    </form>
</div>
