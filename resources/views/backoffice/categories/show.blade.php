@extends('backoffice.layout', ['title' => 'Modifica categoria', 'active' => $path])

@section('main-content')
    <div class="d-flex justify-content-between">
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button class="btn-back" emphasis="outlined" leading="fa-arrow-left" />
            </div>
            <div>
                <x-breadcrumb :first="['Categorie', 'categories.index']" :second="[$model->label]" />
                <x-header-page :title="$model->label" />
            </div>
        </div>
        <div class="d-flex gap-3 align-items-center">
            <div>
                <x-button class="btn-save-category" emphasis="primary" label="Salva modifiche" leading="fa-save" />
            </div>
        </div>
    </div>
    <div class="w-100">
        <form id="update-category-form">
            <div class="row">
                <div class="col-12">
                    <x-card title="Informazioni categoria" sub_title="Dati principali della categoria">
                        <div class="row">
                            <div class="col-12 col-sm-6">
                                <x-input :model="$model" name="label" label="Nome categoria" required />
                            </div>
                            <div class="col-12 col-sm-6">
                                <x-input :model="$model" name="category_code" label="Codice categoria" required />
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 col-sm-6">
                                <x-input :model="$model" name="iva" type="number" label="IVA (%)" />
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('custom-script')
    <script>
        $(document).ready(function () {
            $(document).on('click', '.btn-back', function () {
                window.history.back();
            });

            $(document).on('click', '.btn-save-category', function () {
                const data = {
                    label:         $(`#update-category-form input[name='label']`).val(),
                    category_code: $(`#update-category-form input[name='category_code']`).val(),
                    iva:           $(`#update-category-form input[name='iva']`).val(),
                    _method:       'PUT',
                };

                $(document).trigger('fetch', [{
                    path: `/categories/{{ $model->id }}`,
                    method: 'post',
                    data: data,
                    then: (response) => {
                        toastr.success('Categoria aggiornata con successo');
                    },
                    catch: (response) => {
                        toastr.error(response.responseJSON?.message ?? 'Errore durante il salvataggio');
                    },
                }]);
            });
        });
    </script>
@endsection
