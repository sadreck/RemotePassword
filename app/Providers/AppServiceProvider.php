<?php

namespace App\Providers;

use App\Services\ConfigHelper;
use App\Services\Core\Settings;
use App\Services\KeyManager;
use App\Services\PasswordComplexity;
use App\Services\PasswordLogManager;
use App\Services\RemotePasswordManager;
use App\Services\UserManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();

        $this->app->singleton('settings', function () {
            return new Settings();
        });

        $this->app->singleton('userManager', function () {
            return new UserManager(
                app()->make('passwordComplexity')
            );
        });

        $this->app->singleton('passwordComplexity', function () {
            /** @var Settings $settings */
            $settings = app()->make('settings');
            return new PasswordComplexity(
                $settings->get('password_length', 14, 'int'),
                $settings->get('password_letters', true, 'bool'),
                $settings->get('password_mixedcase', true, 'bool'),
                $settings->get('password_numbers', true, 'bool'),
                $settings->get('password_symbols', true, 'bool'),
                $settings->get('password_uncompromised', true, 'bool'),
            );
        });

        $this->app->singleton('keyManager', function () {
            return new KeyManager();
        });

        $this->app->singleton('passwordManager', function () {
            return new RemotePasswordManager(
                app()->make('keyManager'),
                app()->make('passwordLogManager')
            );
        });

        $this->app->singleton('passwordLogManager', function () {
            return new PasswordLogManager();
        });

        $this->app->singleton('configHelper', function () {
            return new ConfigHelper(app()->make('settings'));
        });
    }
}
