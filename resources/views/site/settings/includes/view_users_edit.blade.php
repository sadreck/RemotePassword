<div class="row">
    <div class="col">
        <h4>{{ $userId > 0 ? __('Edit User') : __('Create User') }}</h4>

        <form method="post" action="{{ route('siteSettingsUserEditSave', ['id' => $userId]) }}">
            @csrf

            <div class="mb-3">
                <label for="username" class="form-label">{{ __('Username') }}</label>
                <input name="username" type="text" class="form-control @error('username') is-invalid @enderror" id="username" autofocus required value="{{ old('username') ?? ($userId > 0 ? $user->username : '') }}">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input name="email" type="email" class="form-control @error('email') is-invalid @enderror" id="email" required value="{{ old('email') ?? ($userId > 0 ? $user->email : '') }}">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <input name="password" type="password" class="form-control @error('password') is-invalid @enderror" id="password" value="">
            </div>

            <div class="mb-3">
                <div class="form-check form-switch switch-on-the-right">
                    <input name="enabled" class="form-check-input float-end" type="checkbox" role="switch" id="enabled" {{ old('enabled') ? 'checked' : ($userId > 0 && !$user->isEnabled() ? '' : 'checked') }} value="1">
                    <label class="form-check-label" for="enabled">{{ __('Enabled') }}</label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch switch-on-the-right">
                    <input name="activated" class="form-check-input float-end" type="checkbox" role="switch" id="activated" {{ old('activated') ? 'checked' : ($userId > 0 && !$user->isActivated() ? '' : 'checked') }} value="1">
                    <label class="form-check-label" for="activated">{{ __('Activated') }}</label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch switch-on-the-right">
                    <input name="admin" class="form-check-input float-end" type="checkbox" role="switch" id="admin" {{ old('admin') ? 'checked' : ($userId > 0 && $user->isAdmin() ? 'checked' : '') }} value="1">
                    <label class="form-check-label" for="admin">{{ __('Admin') }}</label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch switch-on-the-right">
                    <input name="locked" class="form-check-input float-end" type="checkbox" role="switch" id="locked" {{ old('locked') ? 'checked' : ($userId > 0 && $user->isLocked() ? 'checked' : '') }} value="1">
                    <label class="form-check-label" for="locked">{{ __('Locked') }}</label>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
