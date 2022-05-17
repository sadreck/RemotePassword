<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'user_tokens';

    /** @var string[] */
    protected $fillable = [
        'user_id',
        'type',
        'token',
        'used',
        'active'
    ];

    /** @var string[] */
    protected $casts = [
        'used' => 'boolean',
        'active' => 'boolean'
    ];
}
