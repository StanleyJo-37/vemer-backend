<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function getLeaderboard(int $leaderboard_id, Request $request){
        try {
            $request->validate([
                'per_page' => 'required|integer',
            ]);

            $leaderboard = DB::table('leaderboards as l')
                                ->select([
                                    'l.id',
                                    'l.name',
                                    'u.id',
                                    'u.name',
                                    DB::raw('SUM(up.point) as points')
                                ])
                                ->join('user_points as up', function ($join) {
                                    $join->on('up.leaderboard_id', '=', 'l.id')
                                        ->where('l.is_active', true);
                                })
                                ->join('users as u', 'u.id', '=', 'up.id')
                                ->where('l.id', $leaderboard_id)
                                ->sortBy("points", "ASC")
                                ->paginate($request->per_page);

            return response()->json($leaderboard);
        } catch (Exception $e){
            throw $e;
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
            if($category == NULL){
                // full active user from all category
            } else {
                // categorical total points earned
            }
        } catch (Exception $e){
            throw $e;
        }
    }


    public function totalEventsCompleted(Request $request){
        $category = $request->category;
        try{
            if($category == NULL){
                // full active user from all category
            } else {
                // categorical total events completed
            }
        } catch (Exception $e){
            throw $e;
        }
    }


}
