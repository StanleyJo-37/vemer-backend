<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;


class ActivityController extends Controller
{
    public function getActivityById(Request $request, int $id)
    {
        $activity = Activity::find($id);

        if($activity == NULL){
            return response()->json([
                'message' => 'Activity not found !',
                'error' => true
            ], 404);
        }

        return response()->json([
            'message' => 'Activity found successfully',
            'error' => false,
            'item' => $activity
        ]);
    }
}
