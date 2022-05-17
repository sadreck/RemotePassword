<?php
namespace App\Services;

use App\Models\User;
use App\Services\Core\Settings;
use App\Services\ReturnTypes\NotificationChannel;

class ConfigHelper
{
    /**
     * @param Settings $settings
     */
    public function __construct(protected Settings $settings)
    {
        //
    }

    /**
     * @return bool
     */
    public function isEmailEnabled() : bool
    {
        return $this->settings->get('email_enabled', false, 'bool');
    }

    /**
     * @return bool
     */
    public function isEmailPasswordSuccessErrorEnabled() : bool
    {
        return $this->settings->get('password_email_notifications_enabled', false, 'bool');
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isSlackEnabled(User $user) : bool
    {
        return !empty($user->getGlobalSlackWebhook());
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isDiscordEnabled(User $user) : bool
    {
        return !empty($user->getGlobalDiscordWebhook());
    }

    /**
     * @param NotificationChannel $channel
     * @param User $user
     * @return bool
     */
    public function isNotificationChannelEnabled(NotificationChannel $channel, User $user) : bool
    {
        return match ($channel) {
            NotificationChannel::EMAIL => $this->isEmailEnabled() && $this->isEmailPasswordSuccessErrorEnabled(),
            NotificationChannel::SLACK => $this->isSlackEnabled($user),
            NotificationChannel::DISCORD => $this->isDiscordEnabled($user),
            default => false,
        };
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasAnyNotificationChannels(User $user) : bool
    {
        return $this->isEmailEnabled() || $this->isSlackEnabled($user) || $this->isDiscordEnabled($user);
    }

    /**
     * @param NotificationChannel $channel
     * @param User $user
     * @return bool
     */
    public function isNotificationChannelEnabledByDefault(NotificationChannel $channel, User $user) : bool
    {
        return match ($channel) {
            NotificationChannel::SLACK => $user->getDefaultSlackState(),
            NotificationChannel::DISCORD => $user->getDefaultDiscordState(),
            default => false,
        };
    }
}
