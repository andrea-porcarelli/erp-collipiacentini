@extends('whitelabel.layout', compact('partner'))

@section('head')
    <title>{{ $title }} - {{ $partner->partner_name }}</title>
@endsection

@section('content')
    <div class="container mt-5" style="min-height: 600px">
        <div class="row w-100">
            <div class="col-12 col-md-10 offset-md-1">
                <div class="hero mt-spacing-2xl mb-spacing-xl">
                    <h1>{{ $title }} - {{ $partner->partner_name }}</h1>
                </div>

                <div class="partner-page-content">
                    @if(trim(strip_tags($content)) === '')
                        <p class="text-muted">{{ __('whitelabel.page.empty') }}</p>
                    @else
                        {!! $content !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
