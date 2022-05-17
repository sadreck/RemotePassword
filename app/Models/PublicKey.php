<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicKey extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'public_keys';

    /** @var array */
    protected $fillable = [
        'user_id',
        'label',
        'description',
        'data'
    ];

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }
}
