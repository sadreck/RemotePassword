<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'error_logs';

    /** @var string[] */
    protected $fillable = [
        'user_id',
        'ip',
        'user_agent',
        'error',
        'details'
    ];

    /**
     * @param string $timezone
     * @param string $dateTimeFormat
     * @return string
     */
    public function getErrorTime(string $timezone = 'UTC', string $dateTimeFormat = 'Y-m-d H:i:s') : string
    {
        $timezone = empty($timezone) ? 'UTC' : $timezone;
        $dateTimeFormat = empty($dateTimeFormat) ? 'Y-m-d H:i:s' : $dateTimeFormat;
        return $this->created_at->setTimezone($timezone)->format($dateTimeFormat);
    }
}
