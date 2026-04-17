@extends('backoffice.layout', ['title' => 'Ordine #' . $model->order_number, 'active' => $path])

@section('main-content')
    @php($order = $model)

    <div class="d-flex justify-content-between align-items-center mb-spacing-2xl">
        <div class="d-flex gap-3 align-items-center">
            <a href="{{ route('orders.index') }}" class="text-decoration-none">
                <x-button status="Primary" emphasis="Low" size="Small" leading="fa-arrow-left" />
            </a>
            <div>
                <x-breadcrumb :first="['Ordini', 'orders.index']" :second="['#' . $model->order_number]" />
                <x-header-page :title="'Ordine #' . $model->order_number" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <x-card title="Riepilogo ordine">
                @include('backoffice.orders._preview', ['order' => $order, 'hideCta' => true])
            </x-card>
        </div>
    </div>
@endsection
