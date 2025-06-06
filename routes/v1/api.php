<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public
Route::prefix('/public')->group(function () {
    Route::prefix('/auth')->group(function() {
        Route::post('/register', [AuthController::class, 'register']);
        Route::prefix('/login')->group(function() {
            Route::post('/', [AuthController::class, 'login']);
            Route::post('/sso', [AuthController::class, 'loginSSO']);
            Route::get('/sso/callback/{provider}', [AuthController::class, 'callbackSSO']);
        });
    });

    Route::prefix('/leaderboard')->group(function(){
        Route::get('/user', [LeaderboardController::class, "getLeaderboard"]);
        Route::get('/total-user', [LeaderboardController::class, 'totalActiveUser']);
        Route::get('/total-points', [LeaderboardController::class, 'totalPointsEarned']);
        Route::get('/active-user', [LeaderboardController::class, 'totalEventsCompleted']);
    });
});

// Auth
Route::prefix('/auth')->middleware('auth:sanctum')->group(function () {
    Route::get('/me', [ProfileController::class, 'me']);
    Route::prefix('/activities')->group(function () {
        Route::get('/', [ActivityController::class, 'get']);
        Route::get('/{id}', [ActivityController::class, 'getDetail']);
    });
});
