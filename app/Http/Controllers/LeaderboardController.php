<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function getLeaderboard(Request $request){
        try {
            $request->validate([
                'per_page' => 'required|integer',
                'category' => 'nullable|string',
            ]);

            $category = $request->category;

            // Base query to get user points
            $userPointsQuery = DB::table('user_points as up');

            // If category is specified, filter by category first, then sum
            if ($category && $category != "all") {
                $userPointsQuery->join('activity_participants as ap', 'up.user_id', '=', 'ap.user_id')
                    ->join('activities as a', 'a.id', '=', 'ap.activity_id')
                    ->where('a.activity_type', $category)
                    ->select('up.user_id', DB::raw('SUM(up.point) as total_points'))
                    ->groupBy('up.user_id');
            } else {
                // If no category is specified, sum all points
                $userPointsQuery->select('up.user_id', DB::raw('SUM(up.point) as total_points'))
                    ->groupBy('up.user_id');
            }

            // Get all activities for the response
            $activities = DB::table('activities as a')
                ->select('a.id', 'a.name', 'a.activity_type')
                ->when($category && $category != "all", function ($query) use ($category) {
                    return $query->where('a.activity_type', $category);
                })
                ->get();

            // Get the user rankings
            $userPoints = $userPointsQuery->get();

            // Map user points to include user details and level
            $rankings = [];
            foreach ($userPoints as $userPoint) {
                $user = DB::table('users')->where('id', $userPoint->user_id)->first();
                if ($user) {
                    $level = 'Bronze';
                    if ($userPoint->total_points >= 5000) {
                        $level = 'Sigma';
                    } elseif ($userPoint->total_points >= 1000) {
                        $level = 'Alpha';
                    } elseif ($userPoint->total_points >= 500) {
                        $level = 'Gold';
                    } elseif ($userPoint->total_points >= 100) {
                        $level = 'Silver';
                    }

                    $rankings[] = [
                        'id' => $user->id,
                        'username' => $user->username,
                        'points' => $userPoint->total_points,
                        'level' => $level
                    ];
                }
            }

            // Sort rankings by points in descending order
            usort($rankings, function($a, $b) {
                return $b['points'] - $a['points'];
            });

            // Paginate the results
            $perPage = $request->per_page;
            $page = $request->input('page', 1);
            $offset = ($page - 1) * $perPage;

            $paginatedRankings = array_slice($rankings, $offset, $perPage);

            $result = [
                'current_page' => (int)$page,
                'data' => [
                    [
                        'ranking' => $paginatedRankings,
                        'activities' => $activities
                    ]
                ],
                'first_page_url' => url()->current() . '?page=1',
                'from' => $offset + 1,
                'last_page' => ceil(count($rankings) / $perPage),
                'last_page_url' => url()->current() . '?page=' . ceil(count($rankings) / $perPage),
                'next_page_url' => $page < ceil(count($rankings) / $perPage) ? url()->current() . '?page=' . ($page + 1) : null,
                'path' => url()->current(),
                'per_page' => $perPage,
                'prev_page_url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                'to' => min($offset + $perPage, count($rankings)),
                'total' => count($rankings)
            ];

            return response()->json($result);
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
