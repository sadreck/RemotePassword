<?php
namespace App\Services\Notifications\Discord;


use Illuminate\Notifications\Notification;

class DiscordChannel
{
    /**
     * @param Discord $discord
     */
    public function __construct(protected Discord $discord)
    {
        //
    }

    /**
     * @param $notifiable
     * @param Notification $notification
     * @return \Psr\Http\Message\ResponseInterface|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send($notifiable, Notification $notification)
    {
        /** @var DiscordMessage $message */
        $message = $notification->toDiscord($notifiable);
        if (empty($message->getWebHookUrl())) {
            $message->to($notifiable->routeNotificationFor('discord', $notification));
        }

        return $this->discord->send($message->getWebHookUrl(), $message->toArray());
    }
}
