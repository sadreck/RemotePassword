<?php
namespace App\Services\Core;

use App\Models\Setting;
use App\Services\Core\Traits\SettingsHelper;

class Settings
{
    use SettingsHelper;

    /**
     * @param string $name
     * @param $default
     * @param string $type
     * @return mixed
     */
    public function get(string $name, $default = null, string $type = '') : mixed
    {
        $item = Setting::where(['name' => $name])->first();
        return $item ? $this->castValue($item->value, $type) : $default;
    }

    /**
     * @param string $name
     * @param $value
     * @return bool
     */
    public function set(string $name, $value) : bool
    {
        $item = Setting::where(['name' => $name])->first();
        if (!$item) {
            $item = new Setting();
            $item->name = $name;
        }
        $item->value = is_null($value) ? '' : $value;
        $item->save();
        return true;
    }
}
