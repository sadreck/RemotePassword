<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordInvalidAccessLog extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'password_invalid_access_logs';

    /** @var string[] */
    protected $fillable = [
        'ip',
        'accessed_at',
        'info'
    ];

    /** @var string[] */
    protected $casts = [
        'accessed_at' => 'datetime'
    ];

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
}
