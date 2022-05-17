<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemotePasswordNotification extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'remote_password_notifications';

    /** @var string[] */
    protected $fillable = [
        'password_id',
        'channel',
        'enabled',
        'on_success',
        'on_error',
    ];

    /** @var string[] */
    protected $casts = [
        'enabled' => 'boolean',
        'on_success' => 'boolean',
        'on_error' => 'boolean'
    ];
}
