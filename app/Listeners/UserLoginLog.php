<?php

namespace App\Listeners;

use App\Services\PasswordLogManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserLoginLog
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
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Login $event)
    {
        /** @var PasswordLogManager $logManager */
        $logManager = app()->make('passwordLogManager');

        $logManager->logUserLogin($event->user);
    }
}
