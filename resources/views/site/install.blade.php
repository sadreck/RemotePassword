@extends('layout.basic')

@section('content')
    <div class="row">
        <div class="col">
            <h1>{{ __('Installation') }}</h1>

            <form method="post" action="{{ route('firstRunSave') }}">
                @csrf

                <div class="mb-3">
                    <h4>{{ __('Create Admin User') }}</h4>
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">{{ __('Username') }}</label>
                    <input name="username" type="text" class="form-control @error('username') is-invalid @enderror" id="username" autofocus required value="{{ old('username') }}">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input name="email" type="email" class="form-control @error('email') is-invalid @enderror" id="email" required value="{{ old('email') }}">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <input name="password" type="password" class="form-control @error('password') is-invalid @enderror" id="password" value="">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
