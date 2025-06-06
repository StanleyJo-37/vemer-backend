<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityParticipants extends Model
{
    //
    protected $table = 'activity_participants';

    protected $fillable = [
        'user_id',
        'activity_id',
    ];
}
