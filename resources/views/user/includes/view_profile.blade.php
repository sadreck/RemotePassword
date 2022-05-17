<div class="row">
    <div class="col">
        <form action="{{ route('userAccountProfileSave') }}" method="post">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="timezone">{{ __('Timezone') }}</label>
                <select name="timezone" id="timezone" class="form-select">
                    <option value=""></option>
                    @foreach ($timezones as $timezone)
                        <option value="{{ $timezone }}" {{ $user->getTimezone() == $timezone ? 'selected' : '' }}>{{ $timezone }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label" for="datetime_format">{{ __('Date/Time Format') }}</label>
                <input type="text" name="datetime_format" id="datetime_format" class="form-control" value="{{ $user->getDateTimeFormat() }}">
                <div class="font-smaller">https://www.php.net/manual/en/datetime.format.php</div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
