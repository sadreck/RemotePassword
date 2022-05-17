<?php

namespace App\Listeners;

use App\Notifications\LoginNotification;
use App\Services\PasswordLogManager;
use Illuminate\Auth\Events\Login;

class UserLoginEmail
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
     * @param Login $event
     * @return void
     */
    public function handle(Login $event)
    {
        $accessData = $event->accessData ?? null;
        if ($accessData == null) {
            $accessData = [
                'ipAddress' => \Request()->ip(),
                'userAgent' => \Request()->userAgent(),
            ];
        }

        try {
            $event->user->notify(new LoginNotification($event->user, $accessData));
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
        }
    }
}
