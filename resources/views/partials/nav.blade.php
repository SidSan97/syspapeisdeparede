<header class="navbar navbar-expand-lg py-0 sticky-top bd-navbar">
    <nav class="container-fluid px-md-3 px-lg-4">

        <a class="navbar-brand d-inline-flex align-items-center m-0 p-0 me-lg-6 me-xl-9 p-1 rounded text-reset" href="/">
            <h2 class="fw-semibold fs-5 ls-wide ms-2 mb-0">{{ config('app.name') }}</h2>
        </a>

        <button class="nav-link px-3 text-white d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar2" aria-controls="offcanvasNavbar2" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar2" aria-labelledby="offcanvasNavbar2Label">
            <div class="offcanvas-header border-bottom px-5">
                <h5 class="offcanvas-title" id="offcanvasNavbar2Label"></h5>

                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#offcanvasNavbar2"
                    aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">

                @include('partials.sidebar')

                <ul class="navbar-nav ms-auto">

                    <li class="nav-item dropdown">
                        <a href="javascript:;" class="nav-link" id="bd-theme" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="theme-icon-active d-none d-sm-inline-block">
                                <i class="fa fa-sun"></i>
                            </span>
                            <span class="d-sm-none" id="bd-theme-text">Alternar tema</span>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item d-flex justify-content-between align-items-center" data-bs-theme-value="light">
                                    <span><i class="fa fa-sun me-2"></i>Claro</span>
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item d-flex justify-content-between align-items-center" data-bs-theme-value="dark">
                                    <span><i class="fa fa-moon me-2"></i>Escuro</span>
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item d-flex justify-content-between align-items-center" data-bs-theme-value="auto">
                                    <span><i class="fa fa-adjust me-2"></i>Auto</span>
                                </button>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <router-link :to="'/settings'" class="nav-link px-2">
                            <i class="fa fa-cog"></i>
                        </router-link>
                    </li>

                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link" href="#" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            <img class="avatar avatar-lg" src="{{ Auth::user()->avatar ? asset(Storage::url(Auth::user()->avatar)) : asset('assets/img/avatar.svg') }}" alt="Avatar">
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            
                            <div class="dropdown-item">
                                <div class="d-flex gap-3">
                                    <img class="avatar avatar-lg" src="{{ Auth::user()->avatar ? asset(Storage::url(Auth::user()->avatar)) : asset('assets/img/avatar.svg') }}" alt="Avatar">
                                    <div>
                                        <strong>{{ Str::limit(Auth::user()->name, 15) }}</strong>
                                        <p class="text-muted m-0">{{ Auth::user()->email }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <router-link
                                :to="'/profile'"
                                class="dropdown-item"
                            >
                                <i class="fas fa-user me-2"></i> Perfil
                            </router-link>

                            <a class="dropdown-item" href="{{ route('logout') }}"
                                onclick="event.preventDefault();
                                            document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i> {{ __('Logout') }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

    </nav>
</header>
