@extends('whitelabel.layout', compact('partner'))

@section('head')
    <title>{{ $title }} - {{ $partner->partner_name }}</title>
@endsection

@section('content')
    <div class="container mt-5" style="min-height: 600px">
        <div class="row w-100">
            <div class="col-12 col-md-10 offset-md-1">
                <div class="hero mt-spacing-2xl mb-spacing-xl text-center">
                    <h1>{{ $title }} {{ $partner->partner_name }}</h1>
                </div>

                @if($page === 'contatti')
                    @php
                        $contactEmail = $partner->email_notify;
                        $contactPhone = $partner->phone_number;
                        $contactAddress = $partner->structure_address;
                        $contactCards = [
                            ['icon' => 'fa-envelope', 'value' => $contactEmail, 'href' => $contactEmail ? 'mailto:' . $contactEmail : null],
                            ['icon' => 'fa-phone',    'value' => $contactPhone, 'href' => $contactPhone ? 'tel:' . preg_replace('/\s+/', '', $contactPhone) : null],
                            ['icon' => 'fa-location-dot', 'value' => $contactAddress, 'href' => null],
                        ];
                    @endphp

                    <div class="contact-cards">
                        @foreach($contactCards as $card)
                            <div class="contact-card">
                                <div class="contact-card-icon">
                                    <i class="fa-regular {{ $card['icon'] }}"></i>
                                </div>
                                <div class="contact-card-value">
                                    @if($card['value'])
                                        @if($card['href'])
                                            <a href="{{ $card['href'] }}">{{ $card['value'] }}</a>
                                        @else
                                            {{ $card['value'] }}
                                        @endif
                                    @else
                                        <span class="contact-card-empty">—</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="partner-page-content">
                        @if(trim(strip_tags($content)) === '')
                            <p class="text-muted">{{ __('whitelabel.page.empty') }}</p>
                        @else
                            {!! $content !!}
                        @endif
                    </div>
                @else
                    <div class="partner-page-content">
                        @if(trim(strip_tags($content)) === '')
                            <p class="text-muted">{{ __('whitelabel.page.empty') }}</p>
                        @else
                            {!! $content !!}
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($page === 'contatti')
        <style>
            .contact-cards {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 24px;
                margin-top: 24px;
            }
            .contact-card {
                background: #ffffff;
                border-radius: 18px;
                padding: 36px 24px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                gap: 18px;
                min-height: 180px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }
            .contact-card-icon {
                font-size: 36px;
                color: var(--brand-primary-brand, #d24600);
                line-height: 1;
            }
            .contact-card-value {
                font-weight: 700;
                font-size: 15px;
                color: var(--text-main, #1a1a1a);
                word-break: break-word;
            }
            .contact-card-value a {
                color: inherit;
                text-decoration: none;
            }
            .contact-card-value a:hover {
                text-decoration: underline;
            }
            .contact-card-empty {
                color: var(--text-secondary, #6b7280);
                font-weight: 400;
            }

            @media (max-width: 767.98px) {
                .contact-cards {
                    grid-template-columns: 1fr;
                    gap: 12px;
                }
                .contact-card {
                    flex-direction: row;
                    align-items: center;
                    justify-content: flex-start;
                    text-align: left;
                    padding: 18px 22px;
                    min-height: 0;
                    gap: 18px;
                    border-radius: 14px;
                }
                .contact-card-icon {
                    font-size: 24px;
                    flex: 0 0 auto;
                }
                .contact-card-value {
                    font-size: 14px;
                }
            }
        </style>
    @endif
@endsection
