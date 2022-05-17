<div class="row mb-3">
    <div class="col">
        <form action="{{ route('siteSettingsSave', ['view' => $view]) }}" method="post">
            @csrf

            <div class="mb-3">
                <div class="form-check form-switch switch-on-the-right">
                    <input name="password_email_notifications_enabled" class="form-check-input float-end" type="checkbox" role="switch" id="password_email_notifications_enabled" value="1" {{ $settings->get('password_email_notifications_enabled', false, 'bool') ? 'checked' : '' }}>
                    <label class="form-check-label" for="password_email_notifications_enabled">{{ __('Enable E-mail Password Success/Error Notifications') }}</label>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
