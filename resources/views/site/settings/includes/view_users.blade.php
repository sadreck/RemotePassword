<div class="row mb-3">
    <div class="col">
        <div class="text-end">
            <a href="{{ route('siteSettingsUserEdit', ['id' => 0]) }}" class="btn btn-primary">{{ __('Add User') }}</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>#</th>
                    <th class="w-50">{{ __('Username') }}</th>
                    <th class="w-50">{{ __('Email') }}</th>
                    <th>{{ __('Enabled') }}</th>
                    <th>{{ __('Activated') }}</th>
                    <th>{{ __('Admin') }}</th>
                    <th>{{ __('Locked') }}</th>
                    <th>{{ __('Passwords') }}</th>
                    <th class="text-nowrap text-end">{{ __('Last Login') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email }}</td>
                        <td class="text-center"><i class="fa-solid {{ $user->isEnabled() ? 'fa-check text-success' : 'fa-xmark text-danger' }}"></i></td>
                        <td class="text-center"><i class="fa-solid {{ $user->isActivated() ? 'fa-check text-success' : 'fa-xmark text-danger' }}"></i></td>
                        <td class="text-center"><i class="fa-solid {{ $user->isAdmin() ? 'fa-check text-success' : 'fa-xmark text-danger' }}"></i></td>
                        <td class="text-center"><i class="fa-solid {{ $user->isLocked() ? 'fa-check text-danger' : 'fa-xmark text-success' }}"></i></td>
                        <td class="text-center">{{ $user->getPasswords()->count() }}</td>
                        <td class="text-end text-nowrap">{{ $user->getLastLogin() }}</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('siteSettingsUserEdit', ['id' => $user->getId()]) }}" class="text-primary"><i class="fa-solid fa-pen"></i></a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
