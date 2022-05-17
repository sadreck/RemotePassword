@php
$navLinks = [
    'details' => 'Details',
    'access' => 'Access',
    'logs' => 'Logs',
    'restrictions' => 'Restrictions'
];
if ($hasNotificationChannels) {
    $navLinks['notifications'] = 'Notifications';
}
@endphp

<div class="d-md-block d-none">
    <ul class="nav nav-tabs">
        @foreach ($navLinks as $link => $label)
            <li class="nav-item">
                <a class="nav-link {{ $view == $link ? 'active' : '' }}" aria-current="page" href="{{ route('managePassword', ['id' => $password->id, 'view' => $link]) }}">{{ __($label) }}</a>
            </li>
        @endforeach
    </ul>
</div>

<div class="d-md-none">
    <select class="form-select select-navigate-on-change">
        @foreach ($navLinks as $link => $label)
            <option {{ $view == $link ? 'selected' : '' }} value="{{ route('managePassword', ['id' => $password->id, 'view' => $link]) }}">{{ __($label) }}</option>
        @endforeach
    </select>
</div>
