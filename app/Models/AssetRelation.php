<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

class AssetRelation extends Model
{
    //
    protected $table = 'asset_relations';

    protected $fillable = [
        'asset_type',
        'asset_id',
        'model_id',
    ];

    public function assets(): HasOneOrMany {
        return $this->hasMany(
            Asset::class,
            'asset_id',
            'id',
        );
    }
}
