@php($menuPartner = $partner ?? session('partner'))
<nav class="navbar mt-3">
    <div class="container">
        <div class="navbar-container d-flex justify-content-between w-100 align-items-center">
            <div class="menu dropdown">
                <button class="btn btn-link nav-link p-0" type="button" id="navbarDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('whitelabel.menu.toggle') }}">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                    @if($menuPartner)
                        <li><a class="dropdown-item" href="{{ $menuPartner->pageUrl('contatti') }}">{{ __('whitelabel.menu.contacts') }}</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ $menuPartner->pageUrl('privacy-policy') }}">{{ __('whitelabel.menu.privacy_policy') }}</a></li>
                        <li><a class="dropdown-item" href="{{ $menuPartner->pageUrl('cookie-policy') }}">{{ __('whitelabel.menu.cookie_policy') }}</a></li>
                        <li><a class="dropdown-item" href="{{ $menuPartner->pageUrl('termini-condizioni') }}">{{ __('whitelabel.menu.terms_conditions') }}</a></li>
                    @endif
                </ul>
            </div>
            <div class="logo">
                <a href="{{ route('booking.index') }}">
                    <img src="{{ asset('storage/' . Utils::partner_path()) }}" title="{{$partner->partner_name ?? ''}}" />
                </a>
            </div>
            <div class="languages">
                <div class="dropdown">
                    <button class="btn btn-link align-items-center d-flex gap-2" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="{{ asset('whitelabel/images/languages/ita.png') }}" :title="__('whitelabel.languages.italian')" :alt="__('whitelabel.languages.italian')">
                        <span>{{ __('whitelabel.languages.it_short') }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                        <li>
                            <a class="dropdown-item" href="#" data-lang="it">
                                <img src="{{ asset('whitelabel/images/languages/ita.png') }}" :title="__('whitelabel.languages.italian')" :alt="__('whitelabel.languages.italian')"> {{ __('whitelabel.languages.italian') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" data-lang="en">
                                <img src="{{ asset('whitelabel/images/languages/eng.png') }}" :title="__('whitelabel.languages.english')" :alt="__('whitelabel.languages.english')"> {{ __('whitelabel.languages.english') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
