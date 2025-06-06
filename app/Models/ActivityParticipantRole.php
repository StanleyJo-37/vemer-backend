<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityParticipantRole extends Model
{
    //

    protected $table = 'activity_participant_roles';

    protected $fillable = [
        'registration_id',
        'role_id',
    ];
}
