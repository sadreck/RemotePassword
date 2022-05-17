@extends('layout.basic')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <h2>{{ __('Access Logs') }}</h2>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <h4>{{ __('Search') }}</h4>

            <form action="{{ route('accessLogs') }}" method="get">
                <div class="row row-cols-1 row-cols-sm-3">
                    <div class="col">
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <select name="password[]" id="password" class="form-select select-multi" multiple>
                                <option value="">{{ __('Select Password') }}</option>
                                @foreach($passwords as $password)
                                    <option value="{{ $password->getId() }}" {{ in_array($password->getId(), $search->getPasswordIds()) ? 'selected' : '' }}>{{ $password->label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="mb-3">
                            <label for="result" class="form-label">{{ __('Result') }}</label>
                            <select name="result[]" id="result" class="form-select select-multi" multiple>
                                <option value="">{{ __('Select Result') }}</option>
                                @foreach($passwordResultsList as $value => $label)
                                    <option value="{{ $value }}" {{ $search->inResults($value) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="mb-3">
                            <label for="ip" class="form-label">{{ __('IP') }}</label>
                            <input type="text" name="ip" value="{{ $search->getIpAddress() }}" class="form-control" id="ip">
                        </div>
                    </div>
                    <div class="col">
                        <div>
                            <label for="date_from" class="form-label">{{ __('Date') }}</label>
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text">{{ __('From') }}</span>
                            <input type="date" class="form-control text-end" id="date_from" name="date_from" placeholder="YYYY-MM-DD" value="{{ $search->getDateFrom() }}">
                            <span class="input-group-text">{{ __('To') }}</span>
                            <input type="date" class="form-control text-end" id="date_to" name="date_to" placeholder="YYYY-MM-DD" value="{{ $search->getDateTo() }}">
                        </div>
                    </div>
                    <div class="col">
                        <div>
                            <label for="time_from" class="form-label">{{ __('Time') }}</label>
                        </div>
                        <div class="mb-3 input-group">
                            <span class="input-group-text">{{ __('From') }}</span>
                            <input type="time" class="form-control text-end" id="time_from" name="time_from" placeholder="HH:MM" value="{{ $search->getTimeFrom() }}">
                            <span class="input-group-text">{{ __('To') }}</span>
                            <input type="time" class="form-control text-end" id="time_to" name="time_to" placeholder="HH:MM" value="{{ $search->getTimeTo() }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="mb-3 d-grid">
                            <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th class="text-nowrap">{{ __('Password') }}</th>
                        <th class="text-nowrap d-lg-table-cell d-none">{{ __('IP') }}</th>
                        <th class="w-75">{{ __('Result') }}</th>
                        <th class="text-nowrap text-end">{{ __('Date') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="{{ $log->isFailed() ? 'bg-warning': '' }}">{{ $logs->firstItem() + $loop->iteration - 1 }}</td>
                            <td>
                                <div><a href="{{ route('managePassword', ['id' => $log->password_id, 'view' => 'details']) }}">{{ $log->getPassword()->label }}</a></div>
                                <div class="mt-1 d-lg-none">{{ $log->ip }}</div>
                            </td>
                            <td class="d-lg-table-cell d-none">{{ $log->ip }}</td>
                            <td>
                                <!-- Large Screens -->
                                <div class="d-md-block d-none">
                                    <div>{{ $log->getFriendlyResult() }}</div>
                                    <div class="mt-2"><pre class="access-log-info">{{ $log->info }}</pre></div>
                                </div>

                                <!-- Small Screens -->
                                <div class="d-md-none">
                                    <div><a href="#" class="text-secondary log-toggle-info" data-log-id="{{ $log->id }}">{{ $log->getFriendlyResult() }}</a></div>
                                    <div class="mt-2 d-none" id="log-info-{{ $log->id }}"><pre class="access-log-info">{{ $log->info }}</pre></div>
                                </div>
                            </td>
                            <td class="text-end">{{ $log->getAccessTime($user->getTimezone(), $user->getDateTimeFormat()) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center">
                {{ $logs->onEachSide(2)->links() }}
            </div>
        </div>
    </div>
@endsection
