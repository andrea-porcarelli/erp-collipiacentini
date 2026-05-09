@php($customer = $order->customer)
@php($countries = \App\Models\Country::orderBy('position')->orderBy('name')->get(['id', 'name'])->map(fn($c) => ['id' => $c->id, 'label' => $c->name])->all())
<x-modal id="modal-edit-customer" title="Modifica cliente" primary="Salva" secondary="Annulla" width="640px">
    <form id="form-edit-customer">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <x-input label="Nome" name="name" :value="$customer->name" required />
            </div>
            <div class="col-12 col-md-6">
                <x-input label="Cognome" name="surname" :value="$customer->surname" required />
            </div>
            <div class="col-12 col-md-6">
                <x-input label="Email" name="email" type="email" :value="$customer->email" required />
            </div>
            <div class="col-4 col-md-3">
                <x-input label="Prefisso" name="prefix_phone" :value="$customer->prefix_phone" placeholder="+39" />
            </div>
            <div class="col-8 col-md-3">
                <x-input label="Telefono" name="phone" :value="$customer->phone" />
            </div>
            <div class="col-12 col-md-8">
                <x-input label="Indirizzo" name="address" :value="$customer->address" />
            </div>
            <div class="col-6 col-md-4">
                <x-input label="CAP" name="zip_code" :value="$customer->zip_code" />
            </div>
            <div class="col-12 col-md-6">
                <x-input label="Città" name="city" :value="$customer->city" />
            </div>
            <div class="col-12 col-md-6">
                <x-select label="Paese" name="country_id" :value="$customer->country_id" :options="$countries" />
            </div>
            <div class="col-12">
                <x-input label="Codice fiscale" name="fiscal_code" :value="$customer->fiscal_code" />
            </div>
        </div>
    </form>
</x-modal>
