<?php
namespace App\Services\Core;

use App\Models\UserSetting;
use App\Services\Core\Traits\SettingsHelper;

class UserSettings
{
    use SettingsHelper;

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function __construct(protected int $userId)
    {
        //
    }

    /**
     * @param string $name
     * @param $value
     * @return bool
     */
    public function set(string $name, $value): bool
    {
        $item = UserSetting
            ::where('user_id', $this->getUserId())
            ->where('name', $name)
            ->first();
        if (!$item) {
            $item = new UserSetting();
            $item->user_id = $this->getUserId();
            $item->name = $name;
        }
        $item->value = is_null($value) ? '' : $value;
        $item->save();
        return true;
    }

    /**
     * @param string $name
     * @param $default
     * @param string $type
     * @return mixed
     */
    public function get(string $name, $default = null, string $type = ''): mixed
    {
        $item = UserSetting
            ::where('user_id', $this->getUserId())
            ->where('name', $name)
            ->first();
        return $item ? $this->castValue($item->value, $type) : $default;
    }
}
