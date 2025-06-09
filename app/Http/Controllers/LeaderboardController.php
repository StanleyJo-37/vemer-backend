<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaderboardController extends Controller
{
    public function getLeaderboard(Request $request)
    {
        try {
            $validated = $request->validate([
                'per_page' => 'sometimes|integer|min:1',
                'category' => 'nullable|string',
            ]);

            $category = $validated['category'] ?? null;
            $perPage = $validated['per_page'] ?? 15;

            $rankingsQuery = DB::table('users as u')
                ->join('user_points as up', 'u.id', '=', 'up.user_id')
                ->select(
                    'u.id',
                    'u.username',
                    'u.profile_photo_path',
                    DB::raw('SUM(up.point) as total_points'),

                    // --- THIS IS THE NEW LINE FOR RANKING ---
                    // It tells the database to assign a row number after ordering
                    // all users by the sum of their points, descending.
                    DB::raw('RANK() OVER (ORDER BY SUM(up.point) DESC) as rank')

                )
                ->when($category && $category !== 'all', function ($query) use ($category) {
                    $query->whereIn('up.activity_id', function ($subquery) use ($category) {
                        $subquery->select('id')
                            ->from('activities')
                            ->where('activity_type', $category);
                    });
                })
                ->groupBy('u.id', 'u.username', 'u.profile_photo_path')
                ->orderBy('total_points', 'desc');

            $paginatedRankings = $rankingsQuery->paginate($perPage);

            return response()->json($paginatedRankings);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Invalid parameters provided.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Leaderboard Error: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching the leaderboard.'], 500);
        }
    }

    public function totalActiveUser(Request $request){
        $category = $request->category;
        try{
            Log::info("Category: " . $category);
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
                $totalPointsAsNumber = (int) $totalPoints;
                return response()->json($totalPointsAsNumber);
            } else {
                $totalPointsCategory = $query->where('activities.activity_type', $category)
                    ->sum('user_points.point');
                $totalPointsAsNumber = (int) $totalPointsCategory;
                return response()->json($totalPointsAsNumber);
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
