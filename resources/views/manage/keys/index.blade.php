@extends('layout.basic')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>{{ __('Manage Public Keys') }}</h1>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="mb-2 mt-2 text-end">
                    <a class="btn btn-primary" href="{{ route('manageKeysEdit', ['id' => 0]) }}">{{ __('Add Public Key') }}</a>
                </div>

                @if ($userKeys->count() == 0)
                    <div class="alert alert-info text-center">{{ __('You have no public keys yet.') }}</div>
                @else
                    <table class="table table-responsive">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Label') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="d-lg-table-cell d-none">{{ __('Public Key') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($userKeys as $userKey)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $userKey->label }}</td>
                                <td>{{ $userKey->description }}</td>
                                <td class="d-lg-table-cell d-none"><pre class="monospace-smaller">{{ $userKey->data }}</pre></td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('manageKeysEdit', ['id' => $userKey->id]) }}" class="text-primary"><i class="fa-solid fa-pen"></i></a>
                                    <form action="{{ route('manageKeysDelete', ['id' => $userKey->id]) }}" class="d-inline ms-1" method="post" id="delete-key-form-{{ $userKey->id }}">
                                        @csrf
                                        <a href="#" class="confirm-delete text-danger"><i class="fa-solid fa-trash"></i></a>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection
