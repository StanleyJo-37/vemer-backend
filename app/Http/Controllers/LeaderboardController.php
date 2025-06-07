<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    // koneksi category dengan activity,
    public function getLeaderboardByID(int $leaderboard_id, Request $request){
        try {
            $request->validate([
                'per_page' => 'required|integer',
            ]);

            $leaderboard = DB::table('leaderboards as l')
                                ->select([
                                    'l.id',
                                    'l.name',
                                    DB::raw("(
                                        SELECT JSON_AGG(
                                            JSON_BUILD_OBJECT(
                                                // 'id', u.id,
                                                'username', u.username,
                                                'points', user_points.total_points,
                                                'level', CASE
                                                    WHEN user_points.total_points >= 5000 THEN 'Sigma'
                                                    WHEN user_points.total_points >= 1000 THEN 'Alpha'
                                                    WHEN user_points.total_points >= 500 THEN 'Gold'
                                                    WHEN user_points.total_points >= 100 THEN 'Silver'
                                                    ELSE 'Bronze'
                                                END
                                            )
                                            ORDER BY user_points.total_points DESC
                                        )
                                        FROM (
                                            SELECT up.user_id, SUM(up.point) as total_points
                                            FROM user_points up
                                            WHERE up.leaderboard_id = l.id
                                            GROUP BY up.user_id
                                        ) as user_points
                                        JOIN users u ON u.id = user_points.user_id
                                    ) AS ranking"),
                                    DB::raw("(
                                        SELECT JSON_AGG(
                                            JSON_BUILD_OBJECT(
                                                'id', a.id,
                                                'name', a.name
                                            )
                                        )
                                        FROM activities as a
                                        JOIN leaderboard_activity as la ON la.activity_id = a.id
                                        WHERE la.leaderboard_id = l.id
                                    ) AS activities"),
                                ])
                                ->where('l.id', $leaderboard_id)
                                ->where('l.is_active', true)
                                ->paginate($request->per_page);

            return response()->json($leaderboard);
        } catch (Exception $e){
            throw $e;
        }
    }

    public function getLeaderboard(Request $request){
        $category = $request->category;
        if($category == "all"){
            
        } else {

        }
    }

    public function totalActiveUser(Request $request){
        $category = $request->category;
        try{
            // Base query to count distinct users who have participated in activities
            $query = DB::table('activity_participants')
                ->join('activities', 'activity_participants.activity_id', '=', 'activities.id')
                ->join('users', 'activity_participants.user_id', '=', 'users.id')
                ->distinct()
                ->select(DB::raw('COUNT(DISTINCT activity_participants.user_id) as total_active_users'));

            if($category == "all"){
                // Count all active users across all categories
                $totalActiveUsers = $query->count('activity_participants.user_id');
            } else {
                // Count active users for a specific category (activity_type)
                $totalActiveUsers = $query
                    ->where('activities.activity_type', $category)
                    ->count('activity_participants.user_id');
            }

            return response()->json($totalActiveUsers);
        } catch (Exception $e){
            throw $e;
        }
    }

    public function totalPointsEarned(Request $request){
        $category = $request->category;
        try{
            $query = DB::table('activity_participants')
                ->join('activities', 'activity_participants.activity_id', '=', 'activities.id')
                ->join('users', 'activity_participants.user_id', '=', 'users.id')
                ->join('user_points', 'user_points.user_id', '=', 'users.id');

            if($category == "all"){
                $totalPoints = $query->sum('user_points.point');

                return response()->json($totalPoints);
            } else {
                $totalPointsCategory = $query->where('activities.activity_type', $category)
                    ->sum('user_points.point');

                return response()->json($totalPointsCategory);
            }
        } catch (Exception $e){
            throw $e;
        }
    }


    public function totalEventsCompleted(Request $request){
        $category = $request->category;
        try{
            $query = DB::table('activity_participants')
                ->join('activities', 'activity_participants.activity_id', '=', 'activities.id')
                ->where('activities.end_date', '<=', DB::raw('NOW()'));
            if($category == "all"){
                $totalEvents = $query->count('activity_participants.activity_id');

                return response()->json($totalEvents);
            } else {
                $totalEventsCategory = $query->where('activities.activity_type', $category)
                    ->count('activity_participants.activity_id');

                return response()->json($totalEventsCategory);
            }
        } catch (Exception $e){
            throw $e;
        }
    }


}
