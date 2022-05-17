@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2>{{ __('Profile') }}</h2>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col">
                @include('user.includes.menu')
            </div>
        </div>

        <div class="row">
            <div class="col">
                @include($viewToInclude)
            </div>
        </div>
    </div>
@endsection
