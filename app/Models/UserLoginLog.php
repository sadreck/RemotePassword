<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLoginLog extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'user_login_logs';

    /** @var string[] */
    protected $fillable = [
        'user_id',
        'ip',
        'login_at'
    ];

    /** @var string[] */
    protected $casts = [
        'login_at' => 'datetime'
    ];
}
