@component('mail::message')

{{ __('Hello :name', ['name' => $username]) }},

{{ $instructions }}
@component('mail::button', ['url' => $url])
{{ $buttonLabel }}
@endcomponent
@endcomponent
