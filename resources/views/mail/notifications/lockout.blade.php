@component('mail::message')

# {{ __('Your account has been locked due to too many invalid login attempts. The attempt that locked your account is below.') }}

@component('mail::table')
| {{ __('Field') }} | {{ __('Data') }} |
| :- | :- |
@foreach ($fields as $name => $value)
| {{ $name }} | {{ $value }} |
@endforeach
@endcomponent

@endcomponent
