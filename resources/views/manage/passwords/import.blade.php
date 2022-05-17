@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>{{ __('Import Passwords') }}</h1>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <form method="post" action="{{ route('managePasswordsImportRun') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        {{ __('The required fields your CSV file must have are: :fields', ['fields' => $fieldNames]) }}
                    </div>

                    <div class="mb-3">
                        <label for="csv" class="form-label">{{ __('Select CSV File') }}</label>
                        <div class="input-group">
                            <input type="file" class="form-control" aria-label="Upload" name="csv" id="csv">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">{{ __('Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
