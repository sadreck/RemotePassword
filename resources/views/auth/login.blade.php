@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">{{ __('Login') }}</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-2">
                                <label for="username" class="form-label">{{ __('Username') }}</label>
                                <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required autofocus>

                                @error('username')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong> <a href="{{ route('resendAccountActivation') }}">{{ __('Resend Activation Email') }}</a>
                                </span>
                                @enderror

                                @if ($locked)
                                <span class="invalid-feedback d-block" role="alert">
                                    <a href="{{ route('unlockAccount') }}">{{ __('Send Account Unlock Email') }}</a>
                                </span>
                                @endif
                            </div>

                            <div class="mb-2">
                                <label for="password" class="form-label">{{ __('Password') }}</label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" value="" required>

                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="mb-2">
                                <label for="otp" class="form-label">{{ __('OTP') }}</label>
                                <input type="password" name="otp" id="otp" class="form-control @error('otp') is-invalid @enderror" value="" required>
                                @error('otp')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">{{ __('Login') }}</button>
                            </div>
                        </form>
                        <p class="mt-2"><a href="{{ route('forgotPassword') }}">{{ __('Forgotten your password?') }}</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
