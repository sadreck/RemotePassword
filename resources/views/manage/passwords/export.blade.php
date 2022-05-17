@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>{{ __('Export Passwords') }}</h1>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <form method="post" action="{{ route('managePasswordsExportRun') }}">
                    @csrf

                    <table class="table table-responsive">
                        <thead>
                        <tr>
                            <th>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" id="toggle-checks">
                                </div>
                            </th>
                            <th>#</th>
                            <th class="w-50">{{ __('Label') }}</th>
                            <th class="w-50 d-lg-table-cell d-none">{{ __('Description') }}</th>
                            <th class="text-center">{{ __('Enabled') }}</th>
                            <th class="text-end">{{ __('Uses') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($passwords as $remotePassword)
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input password-group" name="id[]" type="checkbox" value="{{ $remotePassword->id }}">
                                    </div>
                                </td>
                                <td>{{ $loop->iteration }}</td>
                                <td><a href="{{ route('managePassword', ['id' => $remotePassword->id, 'view' => 'details']) }}">{{ $remotePassword->label }}</a></td>
                                <td class="d-lg-table-cell d-none">{{ $remotePassword->description }}</td>
                                <td class="text-center">
                                    @if ($remotePassword->enabled)
                                        <i class="fa-solid fa-check text-success"></i>
                                    @else
                                        <i class="fa-solid fa-xmark text-danger"></i>
                                    @endif
                                </td>
                                <td class="text-end">{{ $remotePassword->getUses() }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">{{ __('Export') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('#toggle-checks').change(function () {
                $('.password-group').prop('checked', $(this).is(':checked'));
            });
        });
    </script>
@endsection
