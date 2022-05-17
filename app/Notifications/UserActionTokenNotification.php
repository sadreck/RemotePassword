<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\ReturnTypes\UserTokenType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserActionTokenNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        public User $user,
        public UserTokenType $tokenType
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->getMailSubject())
            ->markdown(
                'mail.notifications.user_action_token',
                [
                    'user' => $this->user,
                    'username' => $this->user->username,
                    'url' => $this->getUrl(),
                    'instructions' => $this->getMailInstructions(),
                    'buttonLabel' => $this->getMailButtonLabel(),
                ]
            );
    }

    /**
     * @return string
     */
    protected function getMailSubject() : string
    {
        return match ($this->tokenType) {
            UserTokenType::ACCOUNT_ACTIVATION => __('Welcome :name! Please activate your account', ['name' => $this->user->username]),
            UserTokenType::PASSWORD_RESET => __('Password Reset'),
            UserTokenType::UNLOCK_ACCOUNT => __('Unlock Account'),
            default => 'RPass Notification',
        };
    }

    /**
     * @return string
     */
    protected function getMailInstructions() : string
    {
        return match ($this->tokenType) {
            UserTokenType::ACCOUNT_ACTIVATION => __('To activate your account please click the following link:'),
            UserTokenType::PASSWORD_RESET => __('To reset your password please click the following link:'),
            UserTokenType::UNLOCK_ACCOUNT => __('To unlock your account please click the following link:'),
            default => 'Code Error: Invalid Token Type',
        };
    }

    /**
     * @return string
     */
    protected function getMailButtonLabel() : string
    {
        return match ($this->tokenType) {
            UserTokenType::ACCOUNT_ACTIVATION => __('Activate'),
            UserTokenType::PASSWORD_RESET => __('Reset'),
            UserTokenType::UNLOCK_ACCOUNT => __('Unlock'),
            default => 'Code Error: Invalid Token Type',
        };
    }

    /**
     * @return string
     */
    protected function getUrl() : string
    {
        switch ($this->tokenType) {
            case UserTokenType::ACCOUNT_ACTIVATION:
                $route = 'activateAccount';
                $token = $this->user->getActivationToken();
                break;
            case UserTokenType::PASSWORD_RESET:
                $route = 'resetPasswordIndex';
                $token = $this->user->getPasswordResetToken();
                break;
            case UserTokenType::UNLOCK_ACCOUNT:
                $route = 'actionUnlockAccount';
                $token = $this->user->getUnlockToken();
                break;
            default:
                return route('home');
        }
        return route($route, ['token' => $token, 'email' => $this->user->email]);
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
