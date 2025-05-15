<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRelation;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    //
    public static function getAsset(int $model_id, string $model_type, ?string $asset_type = null, bool $singleAsset = true) {
        try {
            $assets = AssetRelation::query()
                                    ->select([
                                        'a.id',
                                        'a.file_name',
                                        'a.path',
                                        'a.mime_type',
                                        'ar.asset_type',
                                    ])
                                    ->join('assets as a', 'a.id', '=', 'ar.asset_id')
                                    ->when(isset($asset_type), function ($query) use($asset_type) {
                                        $query->where('ar.asset_type', $asset_type);
                                    })
                                    ->where([
                                        'model_id' => $model_id,
                                        'model_type' => $model_type,
                                    ]);

            $storage = Storage::disk('supabase');

            if ($singleAsset) {
                $asset = $assets->first();
                if ($asset) {
                    $asset->path = $storage->url($asset->path);
                }
                return $asset;
            } else {
                return $assets->get()->map(function($asset) use($storage) {
                    $asset->path = $storage->url($asset->path);
                    return $asset;
                });
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }

    public static function uploadAsset(UploadedFile $file, string $path, int $model_id, string $model_type, string $asset_type) {
        try {
            $storage = Storage::disk('supabase');
            $fileName = time() . '_' . $file->getClientOriginalName();

            $newPath = $storage->putFileAs($path, $file, $fileName);

            if ($path) {
                $asset = Asset::create([
                    'file_name' => $fileName,
                    'path' => $newPath,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
    
                $ar = AssetRelation::create([
                    'asset_type' => $asset_type,
                    'model_id' => $model_id,
                    'asset_id' => $asset->id,
                ]);
    
                return $asset;
            }

            return false;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }
}
