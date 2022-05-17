<?php

namespace App\Listeners;

use App\Events\UserLocked;
use App\Mail\UserLockAccount;
use App\Notifications\LockoutNotification;
use App\Services\PasswordLogManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class UserLockedEmail
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param UserLocked $event
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(UserLocked $event)
    {
        if (!app()->make('configHelper')->isEmailEnabled()) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $accessData = $event->accessData ?? null;
        if ($accessData == null) {
            $accessData = [
                'ipAddress' => \Request()->ip(),
                'userAgent' => \Request()->userAgent(),
            ];
        }

        try {
            $event->user->notify(new LockoutNotification($event->user, $accessData));
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @var PasswordLogManager $logsManager */
            $logsManager = app()->make('passwordLogManager');
            $logsManager->logError(
                $event->user->getId(),
                $accessData['ipAddress'],
                $accessData['userAgent'],
                $e->getMessage(),
                $e->getTraceAsString()
            );
            // @codeCoverageIgnoreEnd
        }
    }
}
