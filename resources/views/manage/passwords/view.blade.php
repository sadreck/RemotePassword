@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <a href="{{ route('managePasswords') }}" class="btn btn-info">{{ __('< return to passwords') }}</a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col">
                <h2 class="float-start" title="{{ $password->description }}">{{ $password->label }}</h2>
                <div class="float-end">
                    <a href="{{ route('managePasswordsEdit', ['id'=> $password->id]) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                    <form action="{{ route('managePasswordsDelete', ['id' => $password->id]) }}" class="d-inline ms-1" method="post" id="delete-password-form-{{ $password->id }}">
                        @csrf
                        <a href="#" class="confirm-delete btn btn-danger btn-sm">{{ __('Delete') }}</a>
                    </form>
                </div>
                <div class="float-none"></div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col">
                @include('manage.passwords.includes.menu')
            </div>
        </div>

        <div class="row">
            <div class="col">
                @include($viewToInclude)
            </div>
        </div>
    </div>
@endsection
