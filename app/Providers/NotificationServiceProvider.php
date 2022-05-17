<?php

namespace App\Providers;

use App\Services\Notifications\Discord\Discord;
use App\Services\Notifications\Discord\DiscordChannel;
use GuzzleHttp\Client;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(DiscordChannel::class)
            ->needs(Discord::class)
            ->give(static function () {
                return new Discord(new Client());
            });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Notification::extend('discord', static function (Container $app) {
            return $app->make(DiscordChannel::class);
        });
    }
}
