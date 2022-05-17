@component('mail::message')

# {{ __('You have just logged in from the following location/browser:') }}

@component('mail::table')
| {{ __('Field') }} | {{ __('Data') }} |
| :- | :- |
@foreach ($fields as $name => $value)
| {{ $name }} | {{ $value }} |
@endforeach
@endcomponent

@endcomponent
