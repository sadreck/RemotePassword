@php
    $navLinks = [
        'profile' => 'Profile',
        'password' => 'Password',
        'notifications' => 'Notifications'
    ];
@endphp

<div class="d-md-block d-none">
    <ul class="nav nav-tabs">
        @foreach ($navLinks as $link => $label)
            <li class="nav-item">
                <a class="nav-link {{ $view == $link ? 'active' : '' }}" aria-current="page" href="{{ route('userAccount', ['view' => $link]) }}">{{ __($label) }}</a>
            </li>
        @endforeach
    </ul>
</div>

<div class="d-md-none">
    <select class="form-select select-navigate-on-change">
        @foreach ($navLinks as $link => $label)
            <option {{ $view == $link ? 'selected' : '' }} value="{{ route('userAccount', ['view' => $link]) }}">{{ __($label) }}</option>
        @endforeach
    </select>
</div>
