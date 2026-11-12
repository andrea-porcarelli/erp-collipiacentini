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
                <x-input name="label" placeholder="es. Prenota ora" maxlength="50" />
            </div>
            <div class="col-12 col-sm-7" >
                <x-input name="link" placeholder="https://www.esempio.it" message="L'URL deve iniziare con http:// o https://" icon="fa-regular fa-circle-info" />
            </div>
            <div class="col-12 col-sm-1 text-end">
                <x-button class="btn-user-delete" size="medium" emphasis="text-only" leading="fa-regular fa-trash icon" />
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
                    <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
                        <div class="text-field-container">
                            <input class="input-miticko" name="label" value="{{ $link->label }}" maxlength="50">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-7">
                    <div class="text-field" data-mode="textfieldSize-Medium textfieldAppearance-Resting">
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
