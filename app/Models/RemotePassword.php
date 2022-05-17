<?php

namespace App\Models;

use App\Services\PasswordLogManager;
use App\Services\ReturnTypes\NotificationChannel;
use App\Services\ReturnTypes\PasswordResult;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class RemotePassword extends Model
{
    use HasFactory, Notifiable;

    /** @var string */
    protected $table = 'remote_passwords';

    /** @var array */
    protected $fillable = [
        'user_id',
        'enabled',
        'label',
        'description',
        'data',
        'public_key_id',
        'token1',
        'token2'
    ];

    /** @var string[] */
    protected $casts = [
        'enabled' => 'boolean'
    ];

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getChecksum() : string
    {
        return hash('sha256', $this->data);
    }

    /**
     * @param bool $log
     * @param Carbon|null $now
     * @return PasswordResult
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function canAccess(bool $log = true, Carbon|null $now = null) : PasswordResult
    {
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        $restrictions = $this->getRestrictions();

        // If there's at least one restriction, the default is to deny by default until we find one that's allowed.
        $allowAccess = ($restrictions->count() == 0);
        $accessResult = $allowAccess ? PasswordResult::SUCCESS : null;
        /** @var RemotePasswordRestriction $restriction */
        foreach ($restrictions as $restriction) {
            $evaluation = $restriction->evaluate($ipAddress, $userAgent, $now, $log);
            if ($evaluation == PasswordResult::SUCCESS) {
                $allowAccess = true;
                break;
            } else {
                $accessResult = $evaluation;
            }
        }

        if ($allowAccess && $log) {
            /** @var PasswordLogManager $logManager */
            $logManager = app()->make('passwordLogManager');

            $logManager->log(
                $this->getId(),
                PasswordResult::SUCCESS,
                "IP Address: {$ipAddress}" . PHP_EOL .
                "User Agent: {$userAgent}",
                $ipAddress,
                $now
            );
        }
        return $allowAccess ? PasswordResult::SUCCESS : $accessResult;
    }

    /**
     * @return Collection
     */
    public function getRestrictions() : Collection
    {
        $items = RemotePasswordRestriction::where('password_id', $this->getId())->get();
        /** @var RemotePasswordRestriction $item */
        foreach ($items as $item) {
            $item->loadRestrictions();
        }
        return $items;
    }

    /**
     * @param int $id
     * @return RemotePasswordRestriction|bool
     */
    public function getRestrictionById(int $id) : RemotePasswordRestriction|bool
    {
        $item = RemotePasswordRestriction
            ::where('password_id', $this->getId())
            ->where('id', $id)
            ->first();
        if (!$item) {
            return false;
        }
        $item->loadRestrictions();
        return $item;
    }

    /**
     * @return bool
     */
    public function resetRestrictions() : bool
    {
        return RemotePasswordRestriction::where('password_id', $this->getId())->delete();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteRestrictionById(int $id) : bool
    {
        return RemotePasswordRestriction
            ::where('password_id', $this->getId())
            ->where('id', $id)
            ->delete();
    }

    /**
     * @return int
     */
    public function getUses() : int
    {
        return $this->used_count;
    }

    /**
     * @param bool $autoSave
     * @return int
     */
    public function increaseUseCount(bool $autoSave = false) : int
    {
        ++$this->used_count;
        if ($autoSave) {
            $this->save();
        }
        return $this->used_count;
    }

    /**
     * @param bool $autoSave
     * @return bool
     */
    public function resetUseCount(bool $autoSave = false) : bool
    {
        $this->used_count = 0;
        if ($autoSave) {
            $this->save();
        }
        return true;
    }

    /**
     * @param NotificationChannel $channel
     * @param bool $enabled
     * @param bool $onSuccess
     * @param bool $onError
     * @return bool
     */
    public function setNotifications(NotificationChannel $channel, bool $enabled, bool $onSuccess, bool $onError) : bool
    {
        $notification = RemotePasswordNotification
            ::where('password_id', $this->getId())
            ->where('channel', $channel)
            ->first();
        if (!$notification) {
            $notification = new RemotePasswordNotification(
                [
                    'password_id' => $this->getId(),
                    'channel' => $channel
                ]
            );
        }
        $notification->enabled = $enabled;
        $notification->on_success = $onSuccess;
        $notification->on_error = $onError;
        return $notification->save();
    }

    /**
     * @param NotificationChannel $channel
     * @return bool
     */
    public function isNotificationChannelEnabled(NotificationChannel $channel) : bool
    {
        $notification = RemotePasswordNotification
            ::where('password_id', $this->getId())
            ->where('channel', $channel)
            ->first();
        return $notification ? $notification->enabled : false;
    }

    /**
     * @param NotificationChannel $channel
     * @return bool
     */
    public function hasSuccessNotifications(NotificationChannel $channel) : bool
    {
        $notification = RemotePasswordNotification
            ::where('password_id', $this->getId())
            ->where('channel', $channel)
            ->first();
        return $notification ? $notification->on_success : false;
    }

    /**
     * @param NotificationChannel $channel
     * @return bool
     */
    public function hasErrorNotifications(NotificationChannel $channel) : bool
    {
        $notification = RemotePasswordNotification
            ::where('password_id', $this->getId())
            ->where('channel', $channel)
            ->first();
        return $notification ? $notification->on_error : false;
    }

    /**
     * @return array
     */
    public function whichNotifications() : array
    {
        $notifications = [];
        foreach (NotificationChannel::cases() as $case) {
            if (!$this->isNotificationChannelEnabled($case)) {
                continue;
            }
            if ($this->hasSuccessNotifications($case) || $this->hasErrorNotifications($case)) {
                $notifications[] = $case->value;
            }
        }

        return $notifications;
    }

    /**
     * @return User|null
     */
    public function getUser() : User|null
    {
        return User::find($this->user_id);
    }

    /**
     * @return bool|null
     */
    public function delete()
    {
        // Delete records from other tables.
        PasswordAccessLog::where('password_id', $this->getId())->delete();
        RemotePasswordNotification::where('password_id', $this->getId())->delete();
        RemotePasswordRestriction::where('password_id', $this->getId())->delete();

        return parent::delete();
    }

    /**
     * @param $notification
     * @return array
     */
    public function routeNotificationForMail($notification)
    {
        $user = $this->getUser();
        return [$user->email => $user->username];
    }

    /**
     * @param $notification
     * @return string
     */
    public function routeNotificationForSlack($notification) : string
    {
        return $this->getUser()->getGlobalSlackWebhook();
    }

    /**
     * @param $notification
     * @return string
     */
    public function routeNotificationForDiscord($notification) : string
    {
        return $this->getUser()->getGlobalDiscordWebhook();
    }
}
