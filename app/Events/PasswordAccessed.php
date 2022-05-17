<?php

namespace App\Events;

use App\Models\RemotePassword;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PasswordAccessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param RemotePassword $password
     * @param array $accessData
     */
    public function __construct(public RemotePassword $password, public array $accessData)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // @codeCoverageIgnoreStart
        return new PrivateChannel('channel-name');
        // @codeCoverageIgnoreEnd
    }
}
