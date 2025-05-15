<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ProfileController extends Controller
{
    //
    public function me() {
        try {
            $user_id = Auth::id();

            $cachedUser = Cache::remember("user:$user_id", 60 * 60 * 2, fn() => User::find($user_id));

            return response()->json(new UserResource($cachedUser));
        } catch (Exception $exception) {
            throw $exception;
        }
    }
}
