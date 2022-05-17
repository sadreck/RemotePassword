@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">{{ __('Setup Two Factor Authentication') }}</div>
                    <div class="card-body">
                        @if ($action == 'activate')
                            <p>{{ __('Before you activate your account, you need to setup your 2FA.') }}</p>
                            <form method="post" action="{{ route('activateAccount2FA', ['token' => $token, 'email' => $email]) }}">
                                @csrf
                                <div class="text-center mb-2">
                                    {!! $qrCodeImage !!}
                                </div>

                                <div class="text-center mb-2">
                                    {{ $otpSecret }}
                                </div>

                                <div class="mb-2">
                                    {{ __('Scan the QR code above or use the text OTP secret to add it to your 2FA application. Once installed, enter a generated OTP code below:') }}
                                </div>

                                <div class="mb-2">
                                    <input type="text" name="otp" value="" class="form-control text-center" required autofocus>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">{{ __('Activate') }}</button>
                                </div>
                            </form>
                        @else
                            <p>{{ __('Below are your backup codes, please copy them to a safe location as you will not be able to view them again.') }}</p>
                            <div class="alert alert-info font-monospace text-center">
                                @foreach($backupCodes as $backupCode)
                                    <div>{{ $backupCode }}</div>
                                @endforeach
                            </div>
                            <div class="d-grid">
                                <a href="{{ route('login') }}" class="btn btn-primary">{{ __('Go to login page') }}</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
