<?php

namespace App\Models;

use App\Services\ReturnTypes\PasswordResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordAccessLog extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'password_access_logs';

    /** @var string[] */
    protected $fillable = [
        'password_id',
        'ip',
        'accessed_at',
        'result',
        'info'
    ];

    /** @var string[] */
    protected $casts = [
        'accessed_at' => 'datetime'
    ];

    /**
     * @return string
     */
    public function getFriendlyResult() : string
    {
        $results = [
            PasswordResult::SUCCESS->value => __('Success'),
            PasswordResult::DISABLED->value => __('Disabled'),
            PasswordResult::RESTRICTION_FAILED_IP->value => __('IP Restriction'),
            PasswordResult::RESTRICTION_FAILED_DATE->value => __('Date Restriction'),
            PasswordResult::RESTRICTION_FAILED_TIME->value => __('Time Restriction'),
            PasswordResult::RESTRICTION_FAILED_DAY->value => __('Day Restriction'),
            PasswordResult::RESTRICTION_FAILED_USERAGENT->value => __('User Agent Restriction'),
            PasswordResult::RESTRICTION_FAILED_MAXUSES->value => __('Max Uses Restriction'),
        ];

        return $results[$this->result] ?? '';
    }

    /**
     * @param string $timezone
     * @param string $dateTimeFormat
     * @return string
     */
    public function getAccessTime(string $timezone = 'UTC', string $dateTimeFormat = 'Y-m-d H:i:s') : string
    {
        $timezone = empty($timezone) ? 'UTC' : $timezone;
        $dateTimeFormat = empty($dateTimeFormat) ? 'Y-m-d H:i:s' : $dateTimeFormat;
        return $this->accessed_at->setTimezone($timezone)->format($dateTimeFormat);
    }

    /**
     * @return bool
     */
    public function isSuccess() : bool
    {
        return $this->result == PasswordResult::SUCCESS->value;
    }

    /**
     * @return bool
     */
    public function isFailed() : bool
    {
        return $this->result != PasswordResult::SUCCESS->value;
    }

    /**
     * @return RemotePassword
     */
    public function getPassword() : RemotePassword
    {
        return RemotePassword::find($this->password_id);
    }
}
