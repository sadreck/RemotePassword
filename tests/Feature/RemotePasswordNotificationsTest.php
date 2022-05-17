<?php

namespace Tests\Feature;

use App\Notifications\PasswordNotification;
use App\Services\ConfigHelper;
use App\Services\RemotePasswordManager;
use App\Services\ReturnTypes\NotificationChannel;
use App\Services\ReturnTypes\PasswordNotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\Shared;
use Tests\TestCase;

class RemotePasswordNotificationsTest extends TestCase
{
    use RefreshDatabase, WithFaker, Shared;

    public function test_Notifications()
    {
        $user = $this->createUser();
        $password = $this->createPassword($user->getId());

        $this->assertTrue($password->setNotifications(NotificationChannel::EMAIL, true, true, true));
        $this->assertTrue($password->isNotificationChannelEnabled(NotificationChannel::EMAIL));
        $this->assertTrue($password->hasSuccessNotifications(NotificationChannel::EMAIL));
        $this->assertTrue($password->hasErrorNotifications(NotificationChannel::EMAIL));

        $this->assertTrue($password->setNotifications(NotificationChannel::EMAIL, false, false, false));
        $this->assertFalse($password->isNotificationChannelEnabled(NotificationChannel::EMAIL));
        $this->assertFalse($password->hasSuccessNotifications(NotificationChannel::EMAIL));
        $this->assertFalse($password->hasErrorNotifications(NotificationChannel::EMAIL));
    }

    public function test_ChannelNotifications()
    {
        /** @var ConfigHelper $configHelper */
        $configHelper = app()->make('configHelper');

        $this->prepareMail();

        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $user = $this->createUser();
        $slackHookUrl = 'http://localhost/webhook/slack';
        $discordHookUrl = 'http://localhost/webhook/discord';

        // Create password notification for both email and slack.
        $user->settings()->set('slack_webhook_url', $slackHookUrl);
        $user->settings()->set('discord_webhook_url', $discordHookUrl);

        $password = $this->createPassword($user->getId());
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                $data = $notification->toMail($notification)->viewData;

                return count($channels) == 3
                    && in_array('mail', $channels)
                    && in_array('slack', $channels)
                    && in_array('discord', $channels)
                    && $notification->notificationType == PasswordNotificationType::CREATED
                    && isset($data['fields']['Date'], $data['fields']['Password']);
            }
        );
        $this->assertArrayHasKey($user->email, $password->routeNotificationForMail(null));
        $this->assertEquals($slackHookUrl, $password->routeNotificationForSlack(null));
        $this->assertEquals($discordHookUrl, $password->routeNotificationForDiscord(null));
        $this->assertTrue($configHelper->hasAnyNotificationChannels($user));

        // Delete password.
        $this->prepareMail();
        $this->assertTrue($passwordManager->deletePassword($password->getId(), $user->getId()));
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                $data = $notification->toMail($notification)->viewData;

                return count($channels) == 3
                    && in_array('mail', $channels)
                    && in_array('slack', $channels)
                    && in_array('discord', $channels)
                    && $notification->notificationType == PasswordNotificationType::DELETED
                    && isset($data['fields']['Date'], $data['fields']['Password']);
            }
        );

        // Re-create a password as we deleted the one above.
        $password = $this->createPassword($user->getId());

        // Update password.
        $this->prepareMail();
        $password = $passwordManager->updatePassword($password->getId(), 'new label', 'new description', $password->description ,$password->data, $password->enabled);
        $this->assertEquals('App\Models\RemotePassword', get_class($password));
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                $data = $notification->toMail($notification)->viewData;

                return count($channels) == 3
                    && in_array('mail', $channels)
                    && in_array('slack', $channels)
                    && in_array('discord', $channels)
                    && $notification->notificationType == PasswordNotificationType::UPDATED
                    && isset($data['fields']['Date'], $data['fields']['Password']);
            }
        );

        // Get password without any notifications set.
        $this->prepareMail();
        $this->assertNotEmpty($passwordManager->retrievePassword($password->token1, $password->token2, ''));
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                return count($channels) == 0;
            }
        );

        // Set email notifications.
        $this->prepareMail();
        $this->assertTrue($password->setNotifications(NotificationChannel::EMAIL, true, true, true));
        $this->assertNotEmpty($passwordManager->retrievePassword($password->token1, $password->token2, ''));
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                return count($channels) == 1 && $channels[0] == 'mail';
            }
        );

        // Disable email and enable slack notifications.
        $this->prepareMail();
        $this->assertTrue($password->setNotifications(NotificationChannel::EMAIL, false, true, true));
        $this->assertTrue($password->setNotifications(NotificationChannel::SLACK, true, true, true));
        $this->assertNotEmpty($passwordManager->retrievePassword($password->token1, $password->token2, ''));
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                return count($channels) == 1 && $channels[0] == 'slack';
            }
        );
        $this->assertCount(1, $password->whichNotifications());
        $this->assertEquals('slack', $password->whichNotifications()[0]);

        // Enable both.
        $this->prepareMail();
        $this->assertTrue($password->setNotifications(NotificationChannel::EMAIL, true, true, true));
        $this->assertTrue($password->setNotifications(NotificationChannel::SLACK, true, true, true));
        $this->assertNotEmpty($passwordManager->retrievePassword($password->token1, $password->token2, ''));
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                return count($channels) == 2
                    && in_array('mail', $channels)
                    && in_array('slack', $channels)
                    && $notification->notificationType == PasswordNotificationType::SUCCESS;
            }
        );

        // Disable success notifications for emails.
        $this->prepareMail();
        $this->assertTrue($password->setNotifications(NotificationChannel::EMAIL, true, false, true));
        $this->assertTrue($password->setNotifications(NotificationChannel::SLACK, true, true, true));
        $this->assertTrue($password->setNotifications(NotificationChannel::DISCORD, true, true, true));
        $this->assertNotEmpty($passwordManager->retrievePassword($password->token1, $password->token2, ''));
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                return count($channels) == 2
                    && in_array('slack', $channels)
                    && in_array('discord', $channels)
                    && $notification->notificationType == PasswordNotificationType::SUCCESS;
            }
        );

        // Disable password.
        $this->prepareMail();
        $this->assertTrue($password->setNotifications(NotificationChannel::EMAIL, true, false, true));
        $this->assertTrue($password->setNotifications(NotificationChannel::SLACK, true, true, true));
        $this->assertTrue($password->setNotifications(NotificationChannel::DISCORD, true, true, true));
        $passwordManager->updatePassword($password->getId(), $password->label, $password->description, $password->data, $password->public_key_id, false);
        $this->assertFalse($passwordManager->retrievePassword($password->token1, $password->token2, ''));
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                return count($channels) == 3
                    && in_array('slack', $channels)
                    && in_array('discord', $channels)
                    && in_array('mail', $channels)
                    && $notification->notificationType == PasswordNotificationType::FAILED;
            }
        );

        // Disable error notifications.
        $this->prepareMail();
        $this->assertTrue($password->setNotifications(NotificationChannel::EMAIL, true, false, false));
        $this->assertTrue($password->setNotifications(NotificationChannel::SLACK, true, true, false));
        $this->assertTrue($password->setNotifications(NotificationChannel::DISCORD, true, true, false));
        $passwordManager->updatePassword($password->getId(), $password->label, $password->description, $password->data, $password->public_key_id, false);
        $this->assertFalse($passwordManager->retrievePassword($password->token1, $password->token2, ''));
        Notification::assertSentTo(
            $password,
            PasswordNotification::class,
            function ($notification, $channels) {
                return count($channels) == 0
                    && $notification->notificationType == PasswordNotificationType::FAILED;
            }
        );

    }
}
// phpcs:ignoreFile
