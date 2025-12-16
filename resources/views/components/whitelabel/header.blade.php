<nav class="navbar navbar-expand-lg mt-3">
    <div class="container">
        <div class="navbar-container d-flex justify-content-between w-100 align-items-center">
            <div class="menu">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMenu">
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-bars"></i>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="#">{{ __('whitelabel.menu.page_1') }}</a></li>
                                <li><a class="dropdown-item" href="#">{{ __('whitelabel.menu.page_2') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">{{ __('whitelabel.menu.other_page') }}</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="logo">
                <img src="{{ asset('whitelabel/companies/' . Utils::company_slug() . '/logo.png') }}" />
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
