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
                    <form id="form-partner-status">
                        <div class="row mt-3">
                            <div class="col-12 col-sm-2">
                                <x-select name="is_active" label="Stato partner" placeholder="Stato partner" required :options="[['id' => 1, 'label' => 'Abilitato'],['id' => 0, 'label' => 'Non Abilitato']]" icon="fa-regular fa-lock-open" :model="$model" />
                            </div>
                        </div>
                    </form>

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
                                <x-input :model="$model" name="commission_presale_low" label="Prevendita (sotto 6,99 €)" />
                            </div>
                            <div class="col-12 col-sm-6">
                                <x-input :model="$model" name="commission_presale_high" label="Prevendita (sopra 7,00 €)" />
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

                <x-card title="Gestione account" class="mt-4 position-relative">
                    <x-backoffice.partner.users :model="$model" />
                    <div class="button-card-absolute">
                        <x-button class="btn-user-add"  status="Secondary" emphasis="MediumLow"  label="Aggiungi account" leading="fa-plus" />
                        <x-button class="btn-save-users" label="Salva modifiche" leading="fa-save" status="Disabled" />
                    </div>
                </x-card>

                <x-card title="Elimina partner" class="mt-4 position-relative">
                    <x-button emphasis="outlined" status="danger" label="Elimina partner" leading="fa-trash" class="btn-delete-partner" />
                </x-card>
            </div>
        </div>
    </div>
@endsection

@section('custom-css')
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
