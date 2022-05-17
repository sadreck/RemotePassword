<div class="row">
    <div class="col">
        <form method="post" action="{{ route('managePasswordNotificationsSave', ['id' => $password->getId()]) }}">
            @csrf

            @foreach($notifications as $channel => $properties)
                <h4>{{ __($properties->label . ' Notifications') }}</h4>

                @foreach ($properties->settings as $property => $setting)
                    <div class="mb-3">
                        <div class="form-check form-switch switch-on-the-right">
                            <input name="{{ $channel }}_{{ $property }}" class="form-check-input float-end" type="checkbox" role="switch" id="{{ $channel }}_{{ $property }}" {{ old($channel . '_' . $property) ? 'checked' : ($setting->value ? 'checked' : '') }} value="1">
                            <label class="form-check-label" for="{{ $channel }}_{{ $property }}">{{ __($setting->label) }}</label>
                        </div>
                    </div>
                @endforeach

            @endforeach

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
