<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function getLeaderboard(Request $request){
        try{

            $pointsSubquery = DB::table('user_awarded_points')
                ->join('user_points', 'user_awarded_points.user_point_id', '=', 'user_points.id')
                ->select(
                    'user_awarded_points.user_id',
                    DB::raw('SUM(user_points.value) as total_points_sum')
                )
                ->where('user_points.is_active', true)
                ->groupBy('user_awarded_points.user_id');

            // Subquery to count distinct activities attended by each user
            $activitiesSubquery = DB::table('activity_participants')
                ->select(
                    'user_id',
                    // Assuming you want to count each distinct activity a user participated in.
                    // If multiple entries for the same activity by the same user should count multiple times, use COUNT(id)
                    DB::raw('COUNT(DISTINCT activity_id) as activities_attended_count')
                )
                ->groupBy('user_id');

            $leaderboardQuery = DB::table('users')
                ->leftJoinSub($pointsSubquery, 'points_data', function ($join) {
                    $join->on('users.id', '=', 'points_data.user_id');
                })
                ->leftJoinSub($activitiesSubquery, 'activities_data', function ($join) {
                    $join->on('users.id', '=', 'activities_data.user_id');
                })
                ->select(
                    'users.id',
                    'users.name',
                    'users.profile_photo_path as avatar', // From your 'users' table schema
                    DB::raw('COALESCE(points_data.total_points_sum, 0) as points'),
                    DB::raw('COALESCE(activities_data.activities_attended_count, 0) as activitiesAttended'),
                    DB::raw('RANK() OVER (ORDER BY COALESCE(points_data.total_points_sum, 0) DESC) as rank'),
                    DB::raw("
                        CASE
                            WHEN COALESCE(points_data.total_points_sum, 0) >= 5000 THEN 'Sigma'
                            WHEN COALESCE(points_data.total_points_sum, 0) >= 1000 THEN 'Alpha'
                            WHEN COALESCE(points_data.total_points_sum, 0) >= 500 THEN 'Gold'
                            WHEN COALESCE(points_data.total_points_sum, 0) >= 100 THEN 'Silver'
                            ELSE 'Bronze'
                        END as level
                    ")
                )
                ->orderBy('rank', 'asc') // Primary sort: by calculated rank
                ->orderBy('points', 'desc') // Secondary sort: by points (for tie-breaking within the same rank if needed by specific RANK behavior)
                ->orderBy('users.name', 'asc'); // Tertiary sort: by user's name for further tie-breaking

            // Fetch the results
            // You might want to paginate or limit the results for large leaderboards
            // For example: $topUsers = $leaderboardQuery->paginate(25);
            // Or: $topUsers = $leaderboardQuery->limit(100)->get();
            $topUsers = $leaderboardQuery->get();

            return response()->json($topUsers);
        } catch (Exception $e){
            throw $e;
        }
    }

    public function totalActiveUser(Request $request){
        $category = $request->category;
        try{
            if($category == NULL){
                // full active user from all category
            } else {
                // categorical total active user
            }


            // return number/int
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


            // return number/int
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

            // return number/int
        } catch (Exception $e){
            throw $e;
        }
    }


}
