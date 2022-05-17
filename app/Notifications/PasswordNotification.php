<?php

namespace App\Notifications;

use App\Models\RemotePassword;
use App\Models\User;
use App\Services\BladeHelper;
use App\Services\ConfigHelper;
use App\Services\Notifications\Discord\DiscordMessage;
use App\Services\ReturnTypes\NotificationChannel;
use App\Services\ReturnTypes\PasswordNotificationType;
use App\Services\ReturnTypes\PasswordResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class PasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public PasswordNotificationType $notificationType,
        public User $user,
        public RemotePassword $password,
        public RemotePassword|null $previousPassword,
        public array|null $accessData = null,
        public PasswordResult|null $accessResult = null,
    ) {
        //
    }

    /**
     * @param NotificationChannel $channel
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function shouldSendNotification(NotificationChannel $channel) : bool
    {
        /** @var ConfigHelper $configHelper */
        $configHelper = app()->make('configHelper');

        if (!$configHelper->isNotificationChannelEnabled($channel, $this->user)) {
            return false;
        }

        $result = false;
        if ($this->password->isNotificationChannelEnabled($channel)) {
            // Success/Error notifications.
            $isSuccess = $this->password->hasSuccessNotifications($channel)
                && $this->notificationType == PasswordNotificationType::SUCCESS;
            $isError = $this->password->hasErrorNotifications($channel)
                && $this->notificationType == PasswordNotificationType::FAILED;

            if ($isSuccess || $isError) {
                $result = true;
            }
        }

        if (!$result) {
            // Create/Update/Delete notifications.
            $isCreated = ($this->notificationType == PasswordNotificationType::CREATED);
            $isUpdated = ($this->notificationType == PasswordNotificationType::UPDATED);
            $isDeleted = ($this->notificationType == PasswordNotificationType::DELETED);

            if ($isCreated || $isUpdated || $isDeleted) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @param $notifiable
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function via($notifiable)
    {
        // We need to determine which channels to send this message to.
        $availableChannels = [
            'mail' => NotificationChannel::EMAIL,
            'slack' => NotificationChannel::SLACK,
            'discord' => NotificationChannel::DISCORD
        ];
        $channels = [];

        foreach ($availableChannels as $name => $channel) {
            if ($this->shouldSendNotification($channel)) {
                $channels[] = $name;
            }
        }
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $subject = __(
            '[ :type ] :label',
            ['type' => __($this->getNotificationTypeLabel()), 'label' => $this->password->label]
        );

        return (new MailMessage)
            ->subject($subject)
            ->markdown(
                'mail.notifications.password',
                [
                    'password' => $this->password,
                    'notificationType' => $this->notificationType,
                    'user' => $this->user,
                    'fields' => $this->getDataFields()
                ]
            );
    }

    /**
     * @return string
     */
    protected function getNotificationTypeLabel() : string
    {
        $data = [
            PasswordNotificationType::SUCCESS->value => 'Success',
            PasswordNotificationType::FAILED->value => 'Denied',
            PasswordNotificationType::CREATED->value => 'Created',
            PasswordNotificationType::UPDATED->value => 'Updated',
            PasswordNotificationType::DELETED->value => 'Deleted'
        ];
        return $data[$this->notificationType->value] ?? 'Unknown';
    }

    /**
     * @param $notifiable
     * @return SlackMessage
     */
    public function toSlack($notifiable)
    {
        $message = new SlackMessage();
        if ($this->notificationType == PasswordNotificationType::FAILED) {
            $message->error();
        } else {
            $message->success();
        }

        $fields = $this->getDataFields();

        $message->attachment(function ($attachment) use ($fields) {
            $attachment->title(
                __('Password :event Notification', ['event' => ucwords($this->notificationType->value)])
            );
            $attachment->fields($fields);
        });

        return $message;
    }

    /**
     * @param $notifiable
     * @return DiscordMessage
     */
    public function toDiscord($notifiable)
    {
        $message = new DiscordMessage();

        $rawFields = $this->getDataFields();
        $fields = [];
        // Convert to discord-compatible fields.
        foreach ($rawFields as $name => $value) {
            $fields[] = ['name' => $name, 'value' => $value];
        }

        $message->embed(function ($embed) use ($fields) {
            if ($this->notificationType == PasswordNotificationType::FAILED) {
                $embed->error();
            } else {
                $embed->success();
            }
            $embed->title(
                __('Password :event Notification', ['event' => ucwords($this->notificationType->value)])
            );
            $embed->fields($fields);
        });

        return $message;
    }

    /**
     * @return array
     */
    protected function getDataFields() : array
    {
        $fields = [];
        if ($this->notificationType == PasswordNotificationType::FAILED) {
            $bladeHelper = new BladeHelper();
            $fields[__('Result')] = $bladeHelper->getFriendlyAccessResult($this->accessResult);
        }
        $fields[__('Password')] = $this->password->label;
        $fields[__('Description')] = $this->password->description;
        if ($this->accessData) {
            $fields[__('IP')] = $this->accessData['ipAddress'];
            $fields[__('User Agent')] = $this->accessData['userAgent'];
        }
        $fields[__('Date')] = $this->user->getDateTimenow();
        return $fields;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
