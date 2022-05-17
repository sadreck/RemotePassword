@inject('bladeHelper', 'App\Services\BladeHelper')
<div class="row">
    <div class="col">
        <div class="mb-2 mt-2 text-end">
            <a class="btn btn-primary" href="{{ route('managePasswordRestrictionEdit', ['id' => $password->getId(), 'restrictionId' => 0]) }}">{{ __('Add Restriction') }}</a>
        </div>

        @if ($password->getRestrictions()->count() == 0)
            <div class="alert alert-info text-center">{{ __('There are currently no restrictions') }}</div>
        @else
            <table class="table table-responsive">
                <thead>
                <tr>
                    <th>#</th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($password->getRestrictions() as $restriction)
                <tr>
                    <td class="w-auto">{{ $loop->iteration }}</td>
                    <td class="w-100">
                        <div class="row row-cols-2">
                            @if ($restriction->hasIpRestrictions())
                                <div class="col mb-2">
                                    <div class="fw-bold border-bottom">{{ __('IP') }}</div>
                                    <div>{!! nl2br(e($restriction->getIPString())) !!}</div>
                                </div>
                            @endif

                            @if ($restriction->hasDateRestrictions())
                                <div class="col mb-2">
                                    <div class="fw-bold border-bottom">{{ __('Date') }}</div>
                                    <div>{!! nl2br(e($restriction->getDatesString())) !!}</div>
                                </div>
                            @endif

                            @if ($restriction->hasTimeRestrictions())
                                <div class="col mb-2">
                                    <div class="fw-bold border-bottom">{{ __('Time') }}</div>
                                    <div>{!! nl2br(e($restriction->getTimeString())) !!}</div>
                                </div>
                            @endif

                            @if ($restriction->hasDayRestrictions())
                                <div class=" colmb-2">
                                    <div class="fw-bold border-bottom">{{ __('Day') }}</div>
                                    <div>
                                        @foreach ($restriction->getWeekdays() as $weekDay)
                                            {{ __($bladeHelper->getWeekDayName($weekDay)) }}<br>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($restriction->hasMaxUsageRestrictions())
                                <div class="col mb-2">
                                    <div class="fw-bold border-bottom">{{ __('Max Uses') }}</div>
                                    <div>{!! nl2br(e($restriction->getMaxUses())) !!}</div>
                                </div>
                            @endif

                            @if ($restriction->hasUserAgentRestrictions())
                                <div class="col mb-2">
                                    <div class="fw-bold border-bottom">{{ __('User Agent') }}</div>
                                    <div>{!! nl2br(e($restriction->getUserAgentString())) !!}</div>
                                </div>
                            @endif
                        </div>

                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('managePasswordRestrictionEdit', ['id' => $password->id, 'restrictionId' => $restriction->id]) }}" class="text-primary"><i class="fa-solid fa-pen"></i></a>
                        <form action="{{ route('managePasswordRestrictionDelete', ['id' => $password->id, 'restrictionId' => $restriction->id]) }}" class="d-inline ms-1" method="post" id="delete-restriction-form-{{ $restriction->id }}">
                            @csrf
                            <a href="#" class="confirm-delete text-danger"><i class="fa-solid fa-trash"></i></a>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
