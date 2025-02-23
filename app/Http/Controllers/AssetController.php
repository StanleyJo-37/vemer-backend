<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRelation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssetController extends Controller
{
    //
    public function getAsset(int $model_id, string $asset_type, bool $singleAsset = true) {
        try {
            $assets = DB::table('asset_relations as ar')
                        ->select([
                            'a.id',
                            'a.file_name',
                            'a.path',
                            'a.mime_type',
                            'ar.asset_type',
                        ])
                        ->join('assets as a', 'a.id', '=', 'ar.asset_id')
                        ->where([
                            'model_id' => $model_id,
                            'asset_type' => $asset_type,
                        ]);

            return $singleAsset ? $assets->first() : $assets->get();            
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }

    public function registerModel(int $model_id, int $asset_id, string $asset_type) {
        try {
            $asset = Asset::create([
                'model_id' => $model_id,
                'asset_id' => $asset_id,
                'asset_type' => $asset_type,
            ]);

            $ar = AssetRelation::create([
                'asset_type' => $asset_type,
                'model_id' => $model_id,
                'asset_id' => $asset->id,
            ]);

            return [
                'asset' => $asset,
                'asset_relation' => $ar,
            ];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }
}
