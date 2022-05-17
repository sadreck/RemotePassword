@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">{{ __('Send Account Unlock Email') }}</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('actionSendUnlockEmail') }}">
                            @csrf

                            <div class="mb-2">
                                <label for="username" class="form-label">{{ __('Username') }}</label>
                                <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required autofocus>
                            </div>

                            <div class="mb-2">
                                <label for="email" class="form-label">{{ __('Email') }}</label>
                                <input type="text" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">{{ __('Send') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
