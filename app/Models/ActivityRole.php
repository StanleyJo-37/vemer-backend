<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityRole extends Model
{
    //
    protected $table = 'activity_roles';

    protected $fillable = [
        'group_id',
        'name',
    ];
}
