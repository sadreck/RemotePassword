@component('mail::message')
@inject('bladeHelper', 'App\Services\BladeHelper')

# {{ __('Password :event Notification', ['event' => ucwords($notificationType->value)]) }}

@component('mail::table')
| {{ __('Field') }} | {{ __('Data') }} |
| :- | :- |
@foreach ($fields as $name => $value)
| {{ $name }} | {{ $value }} |
@endforeach
@endcomponent

@if ($notificationType->value != 'deleted')
@component('mail::button', ['url' => route('managePassword', ['id' => $password->getId(), 'view' => 'details'])])
{{ __('View Password') }}
@endcomponent
@endif

@endcomponent
