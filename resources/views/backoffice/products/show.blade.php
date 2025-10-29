@extends('backoffice.layout', ['title' => 'Modifica prodotto', 'active' => $path])

@section('main-content')
    <x-header-page title="Prodotti" />
    <div class="w-100">
        <div class="row">
            <div class="col-12 col-sm-8">
                <x-card>
                    <x-input name="label" placeholder="Titolo del prodotto" value="{{ $model->label }}" />
                </x-card>
            </div>
            <div class="col-12 col-sm-4">
                <x-card>
                    <x-select name="category_id" label="Categoria" value="{{ $model->label }}" :options="[]" />
                </x-card>
            </div>
        </div>
    </div>
@endsection
