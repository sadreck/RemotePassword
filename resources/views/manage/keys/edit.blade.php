@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>{{ $keyId == 0 ? __('Add Public Key') : __('Edit Public Key') }}</h1>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <form method="post" action="{{ route('manageKeysEditSave', ['id' => $keyId]) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="label" class="form-label">{{ __('Label') }}</label>
                        <input name="label" type="text" class="form-control @error('label') is-invalid @enderror" id="label" autofocus required value="{{ old('label') ?? ($publicKey ? $publicKey->label : '') }}">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('Description') }}</label>
                        <input name="description" type="text" class="form-control" id="description" value="{{ old('description') ?? ($publicKey ? $publicKey->description : '') }}">
                    </div>

                    <div class="mb-3">
                        <label for="data" class="form-label">{{ __('Public Key') }}</label>
                        <textarea name="data" class="form-control font-monospace font-smaller @error('data') is-invalid @enderror" rows="10" id="data" required>{{ old('data') ?? ($publicKey ? $publicKey->data : '') }}</textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
