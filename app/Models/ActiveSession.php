<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'last_activity',
        'ip_address',
        'user_agent',
    ];

protected $casts = [
    'last_activity' => 'datetime',
];

}