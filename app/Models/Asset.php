<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    //
    protected $table = 'assets';

    protected $fillable = [
        'file_name',
        'path',
        'size',
        'mime_type',
    ];
}
