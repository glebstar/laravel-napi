<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'text', 'user_id',
    ];

    protected $hidden = [
        'user_id',
    ];
}
