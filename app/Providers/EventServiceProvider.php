<?php

namespace App\Providers;

use App\Events\PasswordAccessed;
use App\Events\PasswordCreated;
use App\Events\PasswordDeleted;
use App\Events\PasswordFailedAccess;
use App\Events\PasswordUpdated;
use App\Events\UserLocked;
use App\Listeners\PasswordNotification;
use App\Listeners\UserLockedEmail;
use App\Listeners\UserLoginEmail;
use App\Listeners\UserLoginLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Login::class => [
            UserLoginEmail::class,
            UserLoginLog::class
        ],
        PasswordAccessed::class => [
            PasswordNotification::class
        ],
        PasswordFailedAccess::class => [
            PasswordNotification::class
        ],
        PasswordCreated::class => [
            PasswordNotification::class
        ],
        PasswordUpdated::class => [
            PasswordNotification::class
        ],
        PasswordDeleted::class => [
            PasswordNotification::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(
            UserLocked::class,
            [UserLockedEmail::class, 'handle']
        );
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
