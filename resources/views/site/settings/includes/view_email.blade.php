<div class="row mb-3">
    <div class="col">
        <form action="{{ route('siteSettingsSave', ['view' => $view]) }}" method="post">
            @csrf
            <div class="mb-3">
                <div class="form-check form-switch switch-on-the-right">
                    <input name="enabled" class="form-check-input float-end" type="checkbox" role="switch" id="enabled" value="1" {{ $settings->get('email_enabled', false, 'bool') ? 'checked' : '' }}>
                    <label class="form-check-label" for="enabled">{{ __('Enabled') }}</label>
                </div>
            </div>

            <div class="row">
                <div class="col-md">
                    <div class="mb-3">
                        <label for="host" class="form-label">{{ __('Hostname') }}</label>
                        <input type="text" class="form-control" name="host" value="{{ $settings->get('email_host', '') }}" id="port" required autofocus>
                    </div>
                </div>
                <div class="col-md">
                    <div class="mb-3">
                        <label for="port" class="form-label">{{ __('Port') }}</label>
                        <input type="number" class="form-control" name="port" value="{{ $settings->get('email_port', '') }}" id="port" required>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch switch-on-the-right">
                    <input name="tls" class="form-check-input float-end" type="checkbox" role="switch" id="tls" value="1" {{ $settings->get('email_tls', false, 'bool') ? 'checked' : '' }}>
                    <label class="form-check-label" for="tls">{{ __('TLS') }}</label>
                </div>
            </div>

            <div class="row">
                <div class="col-md">
                    <div class="mb-3">
                        <label for="username" class="form-label">{{ __('Username') }}</label>
                        <input type="text" class="form-control" name="username" value="{{ $settings->get('email_username', '') }}" id="username">
                    </div>
                </div>
                <div class="col-md">
                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('Password') }}</label>
                        <input type="password" class="form-control" name="password" value="{{ $settings->get('email_password', '') }}" id="password">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md">
                    <div class="mb-3">
                        <label for="from" class="form-label">{{ __('Email From') }}</label>
                        <input type="text" class="form-control" name="from" value="{{ $settings->get('email_from', '') }}" id="from">
                    </div>
                </div>
                <div class="col-md">
                    <div class="mb-3">
                        <label for="from_name" class="form-label">{{ __('Email From Name') }}</label>
                        <input type="text" class="form-control" name="from_name" value="{{ $settings->get('email_from_name', '') }}" id="from_name">
                    </div>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col">
        <h4>{{ __('Test Email') }}</h4>

        <form method="post" action="{{ route('siteSettingsEmailTest') }}">
            @csrf

            <div class="mb-3">
                <label for="recipient" class="form-label">{{ __('Recipient') }}</label>
                <input type="email" class="form-control" name="recipient" value="" id="recipient" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('Send') }}</button>
            </div>
        </form>
    </div>
</div>
