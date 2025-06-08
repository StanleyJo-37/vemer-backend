<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public
Route::prefix('/public')->group(function () {
    Route::prefix('/auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::prefix('/login')->group(function () {
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
Route::prefix('/auth')->middleware('api', 'auth:sanctum')->group(function () {
    Route::prefix('/auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
    
    Route::get('/me', [ProfileController::class, 'me']);
    Route::prefix('/activities')->group(function () {
        Route::get('/', [ActivityController::class, 'get']);
        Route::get('/{id}', [ActivityController::class, 'getDetail']);
    });

    Route::prefix("/dashboard")->group(function () {
        Route::prefix("/user")->group(function () {
            Route::get('/stats', [DashboardController::class, 'getGeneralStats']);
            Route::get('/upcoming-activities', [UserController::class, 'upcomingActivities']);
            Route::get('/announcements', [UserController::class, 'announcements']);
            Route::get('/recommended-activities', [UserController::class, 'recommendedActivities']);
            Route::get('/recent-activities', [UserController::class, 'recentParticipation']);
            Route::get('/badges', [UserController::class, 'badges']);
            Route::get('/favourite-badges', [UserController::class, 'favouriteBadges']);
            Route::post('/set-favourite-badges', [UserController::class, 'setFavouriteBadges']);
            Route::get('/attended-activities', [UserController::class, 'activitiesAttended']);
            Route::get('/total-points', [UserController::class, 'totalPoints']);
            Route::get('/get-rank', [UserController::class, 'getRank']);
        });

        Route::prefix("/publisher")->group(function (){
            // get total activies hosted
            Route::get('/total-activities', [PublisherController::class, 'totalActivities']);
            // get total participant participated (count of user's approved)
            Route::get('/total-participants', [PublisherController::class, 'totalParticipants']);
            // get total notification sent
            Route::get('/total-notifications', [PublisherController::class, 'totalNotifications']);
            // get activity paginate (also get count of participant that is pending)
            // create activity
            Route::post('/create-activity', [PublisherController::class, 'createActivity']);
            // create badge
            Route::post('/create-badge', [PublisherController::class, 'createBadge']);
            // handle upload image
            Route::post('/upload-image', [PublisherController::class, 'uploadImage']);
            // get notifications paginate
            Route::get('/notifications', [PublisherController::class, 'getNotifications']);
            // approve and unapprove participants
        });
    });
});
