<?php

namespace App\Providers;

use App\Services\Core\Settings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class MailConfigProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            /** @var Settings $settings */
            $settings = app()->make('settings');
            $mappings = [
                'mail.mailers.smtp.host' => $settings->get('email_host', ''),
                'mail.mailers.smtp.port' => $settings->get('email_port', 25, 'int'),
                'mail.mailers.smtp.encryption' => $settings->get('email_tls', false, 'bool') ? 'tls' : null,
                'mail.mailers.smtp.username' => $settings->get('email_username', ''),
                'mail.mailers.smtp.password' => $settings->get('email_password', ''),
                'mail.from.address' => $settings->get('email_from', ''),
                'mail.from.name' => $settings->get('email_from_name', ''),
            ];

            foreach ($mappings as $config => $value) {
                Config::set($config, $value);
            }
        } catch (\Exception $e) {
            //
        }

    }
}
