<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function getLeaderboard(Request $request){
        try{
            $pointsSubquery = DB::table('user_points')
                ->select(
                    'user_points.user_id',
                    DB::raw('SUM(user_points.point) as total_points_sum')
                )
                ->groupBy('user_points.user_id');

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
