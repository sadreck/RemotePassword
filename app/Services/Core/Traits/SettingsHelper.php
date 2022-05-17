<?php
namespace App\Services\Core\Traits;

trait SettingsHelper
{
    /**
     * @param $value
     * @param string $type
     * @return bool|int|float|string
     */
    protected function castValue($value, string $type) : bool|int|float|string
    {
        return match (strtolower($type)) {
            'int', 'integer' => (int)$value,
            'bool', 'boolean' => (bool)$value,
            'float' => (float)$value,
            default => $value
        };
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function setMultiple(array $data) : bool
    {
        foreach ($data as $name => $value) {
            $this->set($name, $value);
        }
        return true;
    }

    /**
     * @param string $name
     * @param $value
     * @return mixed
     */
    abstract public function set(string $name, $value) : bool;

    /**
     * @param string $name
     * @param $default
     * @param string $type
     * @return mixed
     */
    abstract public function get(string $name, $default = null, string $type = '') : mixed;
}
