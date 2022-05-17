@extends('layout.basic')

@section('content')
    <div class="row mb-4">
        <div class="col">
            <h2>{{ __('Error Logs') }}</h2>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <h4>{{ __('Search') }}</h4>

            <form action="{{ route('errorLogs') }}" method="get">
                <div class="row row-cols-1 row-cols-sm-3">
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
                        <th class="text-nowrap">{{ __('IP') }}</th>
                        <th class="w-75">{{ __('Error') }}</th>
                        <th class="text-nowrap text-end">{{ __('Date') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $logs->firstItem() + $loop->iteration - 1 }}</td>
                            <td>{{ $log->ip }}</td>
                            <td>
                                <pre class="access-log-info">{{ $log->error }}</pre>
                                <div>
                                    <div><a href="#" class="text-secondary log-toggle-info" data-log-id="{{ $log->id }}">{{ __('Details') }}</a></div>
                                    <div class="mt-2 d-none" id="log-info-{{ $log->id }}"><pre class="access-log-info">{{ $log->details }}</pre></div>
                                </div>
                            </td>
                            <td class="text-end">{{ $log->getErrorTime($user->getTimezone(), $user->getDateTimeFormat()) }}</td>
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
