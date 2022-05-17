<div class="row">
    <div class="col">
        <form method="post" action="{{ route('userAccountNotificationsSave') }}">
            @csrf

            <h4>{{ __('Slack') }}</h4>

            <div class="mb-3">
                <label class="form-label" for="slack_webhook_url">{{ __('Slack Global Webhook URL') }}</label>
                <input type="url" name="slack_webhook_url" id="slack_webhook_url" class="form-control" value="{{ $user->settings()->get('slack_webhook_url', '') }}">
                <div class="font-smaller">{{ __('This endpoint will be used for all Slack password notifications.') }}</div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch switch-on-the-right">
                    <input name="slack_default_enabled" class="form-check-input float-end" type="checkbox" role="switch" id="slack_default_enabled" value="1" {{ $user->settings()->get('slack_default_enabled', false, 'bool') ? 'checked' : '' }}>
                    <label class="form-check-label" for="slack_default_enabled">{{ __('Enable Slack Notifications on Password Creation') }}</label>
                </div>
            </div>

            <h4>{{ __('Discord') }}</h4>

            <div class="mb-3">
                <label class="form-label" for="discord_webhook_url">{{ __('Discord Global Webhook URL') }}</label>
                <input type="url" name="discord_webhook_url" id="discord_webhook_url" class="form-control" value="{{ $user->settings()->get('discord_webhook_url', '') }}">
                <div class="font-smaller">{{ __('This endpoint will be used for all Discord password notifications.') }}</div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch switch-on-the-right">
                    <input name="discord_default_enabled" class="form-check-input float-end" type="checkbox" role="switch" id="discord_default_enabled" value="1" {{ $user->settings()->get('discord_default_enabled', false, 'bool') ? 'checked' : '' }}>
                    <label class="form-check-label" for="discord_default_enabled">{{ __('Enable Discord Notifications on Password Creation') }}</label>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
