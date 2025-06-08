<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //
    public function getGeneralStats(Request $request) {
        try {
            $user = Auth::user();

            $pointsLevelInterval = 500;

            $stats = DB::table('users as u')
                        ->select([
                            DB::raw('COALESCE(SUM(up.point), 0) AS "totalPoints"'),
                            DB::raw("(
                                SELECT COALESCE(COUNT(ap.id), 0) FROM activity_participants ap WHERE ap.user_id = $user->id
                            ) AS \"eventsParticipated\""),
                            DB::raw("(
                                SELECT COALESCE(COUNT(ub.id), 0) FROM user_badges ub WHERE ub.user_id = $user->id
                            ) AS \"badgesEarned\""),
                        ])
                        ->join('user_points as up', 'up.user_id', '=','u.id')
                        ->where('u.id', $user->id)
                        ->first();

            $stats->pointsToNextLevel = $stats->totalPoints % $pointsLevelInterval;
            $stats->progressToNextLevel = ($stats->pointsToNextLevel / $pointsLevelInterval) * 100;
            $stats->level = floor($stats->totalPoints / $pointsLevelInterval);
            $stats->totalPoints = (int)$stats->totalPoints;

            return response()->json($stats);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
