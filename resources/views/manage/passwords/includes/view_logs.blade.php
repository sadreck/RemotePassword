@if ($logs->count() == 0)
    <div class="alert alert-info text-center">{{ __('There are currently no logs for this password') }}</div>
@else
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <tr>
            <th>#</th>
            <th class="text-nowrap">{{ __('IP') }}</th>
            <th class="w-75">{{ __('Result') }}</th>
            <th class="text-nowrap text-end">{{ __('Date') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($logs as $log)
            <tr class="{{ $log->isFailed() ? 'bg-warning': '' }}">
                <td>{{ $loop->iteration }}</td>
                <td>{{ $log->ip }}</td>
                <td class="text-nowrap">
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
@endif
