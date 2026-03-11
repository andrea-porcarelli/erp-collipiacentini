@props(['model'])

{{-- Form nuovo utente --}}
<div class="pt-3 pb-1">
    <div class="row g-2 align-items-center d-flex justify-content-between mb-1">
        <div class="col-12 col-sm-3 text-field">
            <label>Nome</label>
        </div>
        <div class="col-12 col-sm-3 text-field">
            <label>Email</label>
        </div>
        <div class="col-12 col-sm-3 text-field">
            <label>Password</label>
        </div>
        <div class="col-12 col-sm-3 text-field">
            <label>Ruolo</label>
        </div>
        <div class="col-12 col-sm-1"></div>
    </div>
    <form id="form-user-new">
        <div class="row g-2 align-items-center d-flex justify-content-between">
            <div class="col-12 col-sm-3">
                <x-input name="name" placeholder="es. Mario Rossi" />
            </div>
            <div class="col-12 col-sm-3">
                <x-input name="email" type="email" placeholder="es. mario@example.com" />
            </div>
            <div class="col-12 col-sm-3">
                <x-input name="password" type="password" placeholder="Min. 8 caratteri" />
            </div>
            <div class="col-12 col-sm-2">
                <x-select name="role" required :options="[['id' => 'partner', 'label' => 'Collaboratore'],['id' => 'admin', 'label' => 'Proprietario']]" icon="fa-regular fa-lock-open" />
            </div>
            <div class="col-12 col-sm-1 text-end">
                <x-button size="medium" status="disabled" emphasis="text-only" leading="fa-regular fa-trash icon" disabled="true" />
            </div>
        </div>
    </form>
</div>

{{-- Lista utenti --}}
<div id="users-list" class="mb-3">
    @foreach($model->users as $user)
        <div class="user-item py-1" data-id="{{ $user->id }}">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-sm-3">
                    <x-input name="name" placeholder="es. Mario Rossi" :model="$user" />
                </div>
                <div class="col-12 col-sm-3">
                    <x-input name="email" placeholder="es. Mario Rossi" :model="$user" />
                </div>
                <div class="col-12 col-sm-3">
                    <x-input name="password" type="password" />
                </div>
                <div class="col-12 col-sm-2">
                    <x-select name="role" required :options="[['id' => 'partner', 'label' => 'Collaboratore'],['id' => 'admin', 'label' => 'Proprietario']]" icon="fa-regular fa-lock-open" :model="$user" />
                </div>
                <div class="col-12 col-sm-1 d-flex gap-1 align-items-end justify-content-end pb-1">
                    <x-button class="btn-user-delete" size="medium" emphasis="text-only" leading="fa-regular fa-trash icon" />
                </div>
            </div>
        </div>
    @endforeach
</div>
