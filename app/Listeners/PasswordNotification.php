<?php

namespace App\Listeners;

use App\Events\PasswordAccessed;
use App\Events\PasswordCreated;
use App\Events\PasswordDeleted;
use App\Events\PasswordFailedAccess;
use App\Events\PasswordUpdated;
use App\Models\RemotePassword;
use App\Services\PasswordLogManager;
use App\Services\ReturnTypes\PasswordNotificationType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PasswordNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param $event
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle($event)
    {
        /** @var RemotePassword $password */
        $password = $event->password ?? null;
        if ($password == null) {
            if ($event instanceof PasswordUpdated) {
                $password = $event->before;
            }
        }

        $type = $this->getNotificationType($event);

        $accessData = $event->accessData ?? null;
        if ($accessData == null) {
            $accessData = [
                'ipAddress' => \Request()->ip(),
                'userAgent' => \Request()->userAgent()
            ];
        }

        try {
            $password->notify(new \App\Notifications\PasswordNotification(
                $type,
                $password->getUser(),
                $password,
                $event->before ?? null,
                $accessData,
                $event->accessResult ?? null
            ));
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /** @var PasswordLogManager $logsManager */
            $logsManager = app()->make('passwordLogManager');
            $logsManager->logError(
                $password->getUser()->getId(),
                $accessData['ipAddress'],
                $accessData['userAgent'],
                $e->getMessage(),
                $e->getTraceAsString()
            );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param $event
     * @return PasswordNotificationType
     */
    protected function getNotificationType($event) : PasswordNotificationType
    {
        $type = PasswordNotificationType::NONE;
        if ($event instanceof PasswordAccessed) {
            $type = PasswordNotificationType::SUCCESS;
        } elseif ($event instanceof PasswordFailedAccess) {
            $type = PasswordNotificationType::FAILED;
        } elseif ($event instanceof PasswordCreated) {
            $type = PasswordNotificationType::CREATED;
        } elseif ($event instanceof PasswordUpdated) {
            $type = PasswordNotificationType::UPDATED;
        } elseif ($event instanceof PasswordDeleted) {
            $type = PasswordNotificationType::DELETED;
        }

        return $type;
    }
}
