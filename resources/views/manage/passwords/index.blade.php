@extends('layout.basic')

@section('styles')
    <link href="{{ asset('/css/lib/datatables.min.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>{{ __('Manage Passwords') }}</h1>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="mb-2 mt-2 text-end">
                    <a class="btn btn-primary" href="{{ route('managePasswordsEdit', ['id' => 0]) }}">{{ __('Add') }}</a>
                    <div class="dropdown d-inline">
                        <button class="btn btn-info dropdown-toggle" type="button" id="more-actions" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ __('More') }}
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="more-actions">
                            <li><a class="dropdown-item" href="{{ route('managePasswordsImport') }}">{{ __('Import') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('managePasswordsExport') }}">{{ __('Export') }}</a></li>
                        </ul>
                    </div>
                </div>

                @if ($remotePasswords->count() == 0)
                    <div class="alert alert-info text-center">{{ __('You have no passwords yet.') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table" id="password-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Label') }}</th>
                                <th class="d-lg-table-cell d-none">{{ __('Description') }}</th>
                                <th class="text-center">{{ __('Enabled') }}</th>
                                <th class="text-end">{{ __('Uses') }}</th>
                                <th class="text-end">{{ __('Notifications') }}</th>
                                <th class="text-center text-nowrap"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($remotePasswords as $remotePassword)
                                <tr>
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
                                    <td class="text-end">
                                        @php
                                        $icons = [
                                            'email' => 'fa-solid fa-envelope',
                                            'slack' => 'fa-brands fa-slack',
                                            'discord' => 'fa-brands fa-discord',
                                        ];
                                        @endphp

                                        @foreach ($remotePassword->whichNotifications() as $name)
                                            <span class="ms-1" title="{{ $name }}"><i class="{{ $icons[$name] }}"></i></span>
                                        @endforeach
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ route('managePasswordsEdit', ['id' => $remotePassword->id]) }}" class="text-primary"><i class="fa-solid fa-pen"></i></a>
                                        <form action="{{ route('managePasswordsDelete', ['id' => $remotePassword->id]) }}" class="d-inline ms-1" method="post" id="delete-password-form-{{ $remotePassword->id }}">
                                            @csrf
                                            <a href="#" class="confirm-delete text-danger"><i class="fa-solid fa-trash"></i></a>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ asset('/js/lib/datatables.min.js') }}"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            $('#password-table').DataTable({
                paging: false,
                searching: false,
                info: false,
                columnDefs: [
                    { orderable: false, target: 0 },
                    { orderable: false, target: 3 },
                    { orderable: false, target: 5 },
                    { orderable: false, target: 6 }
                ],
                order: [[1, 'asc']]
            });
        });
    </script>
@endsection
