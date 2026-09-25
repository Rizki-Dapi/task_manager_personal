<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class SystemLog extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'system_logs';

    protected $fillable = [
        'level',
        'message',
        'context',
        'logged_at',
    ];
}
