@props(['model', 'languages'])

{{-- Form nuovo link --}}
<div class="pt-3 pb-1">
    <div class="row g-2 align-items-center d-flex justify-content-between mb-1">
        <div class="col-12 col-sm-4 text-field">
            <label>Nome a menu (50 caratteri max)</label>
        </div>
        <div class="col-12 col-sm-7 text-field">
            <label>URL</label>
        </div>
        <div class="col-12 col-sm-1 text-end">

        </div>
    </div>
    <form id="form-link-new">
        <div class="row g-2 align-items-center d-flex justify-content-between">
            <div class="col-12 col-sm-4">
                <x-input name="label" placeholder="es. Prenota ora" />
            </div>
            <div class="col-12 col-sm-7" >
                <x-input name="link" placeholder="https://..." />
            </div>
            <div class="col-12 col-sm-1 text-end">
                <x-button size="medium" status="disabled" emphasis="light" leading="fa-regular fa-language icon" disabled="true" />
                <x-button size="medium" status="disabled" emphasis="text-only" leading="fa-regular fa-trash icon" disabled="true" />
            </div>
        </div>
    </form>
</div>

{{-- Lista link --}}
<div id="links-list" class="mb-3">
    @foreach($model->links as $link)
        <div class="link-item py-1" data-id="{{ $link->id }}">
            <div class="row g-2 align-items-center ">
                <div class="col-12 col-sm-4">
                    <div class="text-field" data-mode="medium">
                        <div class="text-field-container">
                            <input class="input-miticko" name="label" value="{{ $link->label }}">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-7">
                    <div class="text-field" data-mode="medium">
                        <div class="text-field-container">
                            <input class="input-miticko" name="link" value="{{ $link->link }}">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-1 d-flex gap-1 align-items-end justify-content-end pb-1">
                    <x-button class="btn-link-translations" size="medium" emphasis="light " leading="fa-regular fa-language icon" />
                    <x-button class="btn-link-delete" size="medium"  emphasis="text-only" leading="fa-regular fa-trash icon" />
                </div>
            </div>
        </div>
    @endforeach
</div>
