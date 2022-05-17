<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            Remote Password
            @if (Config::get('app.release') == 'beta')
                <span class="badge bg-primary rounded-pill coming-soon">beta</span>
            @endif
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbar">
            <ul class="navbar-nav ms-auto">
                @guest
                    <li>
                        <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                    </li>

                    @if (Route::has('register'))
                        <li>
                            <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                        </li>
                    @endif
                @else
                    @can('admin')
                        <li>
                            <a class="nav-link" href="{{ route('siteSettings', ['view' => 'general']) }}">{{ __('Site Settings') }}</a>
                        </li>
                    @endcan
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="logs-dropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ __('Logs') }}
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="logs-dropdown">
                            <li><a href="{{ route('accessLogs') }}" class="dropdown-item">{{ __('Access Logs') }}</a></li>
                            @can('admin')
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="{{ route('invalidAccessLogs') }}" class="dropdown-item">{{ __('Invalid Access Logs') }}</a></li>
                                <li><a href="{{ route('errorLogs') }}" class="dropdown-item">{{ __('Error Logs') }}</a></li>
                            @endcan
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="manage-dropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ __('Manage') }}
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="manage-dropdown">
                            <li><a href="{{ route('managePasswords') }}" class="dropdown-item">{{ __('Passwords') }}</a></li>
                            <li><a href="{{ route('manageKeys') }}" class="dropdown-item">{{ __('Public Keys') }}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a href="{{ route('userAccount', ['view' => 'profile']) }}" class="dropdown-item">{{ __('Account') }}</a></li>
                        </ul>
                    </li>
                    <li>
                        <form id="logout-form" action="{{ route('logout') }}" method="post" class="d-none">
                            @csrf
                        </form>
                        <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('Logout') }}</a>
                    </li>
                @endguest
                    <li>
                        <a class="nav-link" href="https://github.com/sadreck/RemotePassword"><i class="fa-brands fa-github"></i></a>
                    </li>
            </ul>
        </div>
    </div>
</nav>
