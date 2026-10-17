@extends('backoffice.layout', ['title' => 'Statistiche · ' . $sectionTitle, 'active' => $active])

@section('main-content')
    <div class="w-100 statistics-page">
        <div class="content-header">
            <small>Statistiche</small>
            <h1 class="mb-0">{{ $sectionTitle }}</h1>
        </div>

        <x-card class="mt-spacing-xl">
            <div class="d-flex flex-column align-items-center justify-content-center text-center py-5">
                <i class="fa-regular fa-chart-column fa-3x text-muted mb-spacing-m"></i>
                <h4 class="mb-spacing-s">Sezione in arrivo</h4>
                <p class="text-muted mb-0">
                    Le statistiche di <strong>{{ $sectionTitle }}</strong> saranno disponibili a breve.
                </p>
            </div>
        </x-card>
    </div>
@endsection
