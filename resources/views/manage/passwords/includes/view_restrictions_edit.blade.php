@inject('bladeHelper', 'App\Services\BladeHelper')

@php
$hasIpRestrictions = ($restrictionId > 0 && $restriction->hasIpRestrictions());
$hasDateRestrictions = ($restrictionId > 0 && $restriction->hasDateRestrictions());
$hasTimeRestrictions = ($restrictionId > 0 && $restriction->hasTimeRestrictions());
$hasDayRestrictions = ($restrictionId > 0 && $restriction->hasDayRestrictions());
$hasUserAgentRestrictions = ($restrictionId > 0 && $restriction->hasUserAgentRestrictions());
$hasMaxUsageRestrictions = ($restrictionId > 0 && $restriction->hasMaxUsageRestrictions());
@endphp

<div class="row">
    <div class="col">
        <form method="post" action="{{ route('managePasswordRestrictionSave', ['id' => $password->getId(), 'restrictionId' => $restrictionId]) }}">
            @csrf
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3">
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header {{ $hasIpRestrictions ? 'bg-warning' : '' }}">{{ __('IP') }}</div>
                        <div class="card-body">
                            <label for="allowed_ips" class="form-label">{{ __('Allowed IP Addresses and IP Ranges') }} ({{ __('one per line') }})</label>
                            <textarea class="form-control" name="allowed_ips" id="allowed_ips" rows="5" placeholder="192.168.10.0/24">{{ old('allowed_ips') ?? ($restrictionId > 0 ? $restriction->getIPString() : '') }}</textarea>
                        </div>
                        <div class="card-footer">
                            <p class="mt-2">{{ __('Example') }}</p>
                            <ul>
                                <li>192.168.0.10 ({{ __('single') }})</li>
                                <li>10.0.0.0/24 ({{ __('range') }})</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header {{ $hasDateRestrictions ? 'bg-warning' : '' }}">{{ __('Date') }}</div>
                        <div class="card-body">
                            <label for="allowed_dates" class="form-label">{{ __('Allowed Dates and Date Ranges') }} ({{ __('one per line') }})</label>
                            <textarea class="form-control" name="allowed_dates" id="allowed_dates" rows="5" placeholder="YYYY-MM-DD">{{ old('allowed_dates') ?? ($restrictionId > 0 ? $restriction->getDatesString() : '') }}</textarea>
                        </div>
                        <div class="card-footer">
                            <p class="mt-2">{{ __('Example') }}</p>
                            <ul>
                                <li>2022-03-28</li>
                                <li>2022-03-28 to 2022-05-01 ({{ __('inclusive') }})</li>
                                <li>from 2022-03-28 ({{ __('inclusive') }})</li>
                                <li>to 2022-05-01 ({{ __('inclusive') }})</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header {{ $hasTimeRestrictions ? 'bg-warning' : '' }}">{{ __('Time') }}</div>
                        <div class="card-body">
                            <label for="allowed_times" class="form-label">{{ __('Allowed Times and Time Ranges') }} ({{ __('one per line') }})</label>
                            <textarea class="form-control" name="allowed_times" id="allowed_times" rows="5" placeholder="HH:MM">{{ old('allowed_times') ?? ($restrictionId > 0 ? $restriction->getTimeString() : '') }}</textarea>
                        </div>
                        <div class="card-footer">
                            <p class="mt-2">{{ __('Example') }}</p>
                            <ul>
                                <li>10:25</li>
                                <li>14:20 to 16:20 ({{ __('inclusive') }})</li>
                                <li>from 14:20 ({{ __('inclusive') }})</li>
                                <li>to 16:20 ({{ __('inclusive') }})</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header {{ $hasDayRestrictions ? 'bg-warning' : '' }}">{{ __('Day') }}</div>
                        <div class="card-body">
                            <label for="allowed_days" class="form-label">{{ __('Allowed Days') }} ({{ __('one per line') }})</label>

                            @foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day)
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="allowed_days[]" id="allowed_day_{{ $day }}" value="{{ $day }}" {{ in_array($day, ($restrictionId > 0 ? $restriction->getWeekdays() : [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allowed_day_{{ $day }}">{{ __($bladeHelper->getWeekDayName($day)) }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header {{ $hasUserAgentRestrictions ? 'bg-warning' : '' }}">{{ __('User Agent') }}</div>
                        <div class="card-body">
                            <label for="allowed_useragent" class="form-label">{{ __('Allowed User Agents') }} ({{ __('one per line') }})</label>
                            <textarea class="form-control" name="allowed_useragent" id="allowed_useragent" rows="5">{{ old('allowed_useragent') ?? ($restrictionId > 0 ? $restriction->getUserAgentString() : '') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-4">
                        <div class="card-header {{ $hasMaxUsageRestrictions ? 'bg-warning' : '' }}">{{ __('Max Uses') }}</div>
                        <div class="card-body">
                            <label for="allowed_maxuses" class="form-label">{{ __('Allowed Max Uses') }}</label>
                            <input type="text" class="form-control text-end" name="allowed_maxuses" id="allowed_maxuses" value="{{ old('allowed_maxuses') ?? ($restrictionId > 0 ? $restriction->getMaxUses() : 0) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid mt-2">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
