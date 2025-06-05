<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public
Route::prefix('/public')->group(function () {
    Route::prefix('/auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::prefix('/login')->group(function () {
            Route::post('/', [AuthController::class, 'login']);
            Route::post('/sso', [AuthController::class, 'loginSSO']);
            Route::post('/sso/callback/{provider}', [AuthController::class, 'callbackSSO']);
        });
    });

    Route::prefix('/leaderboard')->group(function () {
        Route::get('/user', [LeaderboardController::class, "getLeaderboard"]);
        Route::get('/total-user', [LeaderboardController::class, 'totalActiveUser']);
        Route::get('/total-points', [LeaderboardController::class, 'totalPointsEarned']);
        Route::get('/active-user', [LeaderboardController::class, 'totalEventsCompleted']);
    });
});

//
Route::prefix('/auth')->middleware('auth:sanctum')->group(function () {
    Route::get('/me', [ProfileController::class, 'me']);
    Route::prefix('/activities')->group(function () {
        Route::get('/', [ActivityController::class, 'get']);
        Route::get('/{id}', [ActivityController::class, 'getDetail']);
    });

    Route::prefix("/dasboard")->group(function () {
        Route::prefix("/user")->group(function () {
            // get events attended
            // get total point
            // get user global rank
            // get upcoming events
            // get anouncement
            // get recomend
            // get recent participation paginate
            // get user badges
            // get user favourite badge
            // set favourite badge
        });

        Route::prefix("/publisher")->group(function (){
            // get total activies hosted
            // get total participant participated (count of user's approved)
            // get total notification sent
            // get activity paginate (also get count of participant that is pending)
            // create activity
            // handle upload image
            // get notifications paginate
            // approve and unapprove participants
        });
    });
});
