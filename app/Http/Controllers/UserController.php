<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function activitiesAttended(Request $request){
        try {
            $user = Auth::user();

            $events = DB::table('activity_participants')->where('user_id', $user->id)
                        ->join('activities', 'activity_participants.activity_id', '=', 'activities.id')
                        ->select('activities.*')
                        ->when($request->has('limit'), function ($query) use ($request) {
                            $query->limit($request->input('limit'));
                        })
                        ->get();

            return response()->json($events);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function totalPoints(Request $request){
        
        try {
            $user = Auth::user();

            $points = DB::table('user_points')
                        ->where('user_id', $user->id)
                        ->sum('point');

            return response()->json($points);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getRank(Request $request){
        $user = Auth::user();

        try {
            $query = "
                WITH global_scores AS (
                    SELECT
                        user_id,
                        SUM(point) AS total_points
                    FROM
                        user_points
                    GROUP BY
                        user_id
                ),
                ranked_leaderboard AS (
                    SELECT
                        user_id,
                        total_points,
                        RANK() OVER (ORDER BY total_points DESC) as user_rank
                    FROM
                        global_scores
                )
                SELECT
                    user_rank,
                    total_points
                FROM
                    ranked_leaderboard
                WHERE
                    user_id = ?
            ";

            $rankData = DB::selectOne($query, [$user->id]);

            if ($rankData) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'rank' => $rankData->user_rank,
                        'points' => $rankData->total_points,
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found in leaderboard. User may have zero points.',
                ], 404);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function upcomingActivities(Request $request){
        try {
            $user = Auth::user();
            
            $activities = DB::table('activities as a')
                            ->join('activity_participants as ap', 'a.id', '=', 'ap.activity_id')
                            ->where('a.start_date', '>', now())
                            ->where('ap.user_id', $user->id)
                            ->select('a.*')
                            ->when($request->has('limit'), function ($query) use ($request) {
                                $query->limit($request->input('limit'));
                            })
                            ->get();

            return response()->json($activities);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function announcements(Request $request){
        try {
            $user = Auth::user();

            $announcements = DB::table('notifications as n')
                                ->join('user_notification as un', 'n.id', '=', 'un.notification_id')
                                ->where('un.user_id', $user->id)
                                ->select('n.*')
                                ->when($request->has('limit'), function ($query) use ($request) {
                                    $query->limit($request->input('limit'));
                                })
                                ->get();
            
            return response()->json($announcements);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function recommendedActivities(Request $request){
        try {
            $user = Auth::user();

            $activities = DB::table('activities as a')
                            ->where('a.start_date', '>', now())
                            ->orderBy('a.start_date', 'asc')
                            ->when($request->has('limit'), function ($query) use ($request) {
                                $query->limit($request->input('limit'));
                            })
                            ->whereNotIn('a.id', function ($query) use ($user) {
                                $query->select('ap.activity_id')
                                    ->distinct()
                                    ->from('activity_participants as ap')
                                    ->where('ap.user_id', $user->id);
                            })
                            ->get();

            return response()->json($activities);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function recentParticipation(Request $request){
        try{
            $user = Auth::user();

            $pastActivities = DB::table('activity_participants as ap')
                                ->join('activities as a', 'ap.activity_id', '=', 'a.id')
                                ->where('ap.user_id', $user->id)
                                ->where('a.start_date', '<', now())
                                ->orderBy('a.start_date', 'desc')
                                ->select('a.*')
                                ->when($request->has('limit'), function ($query) use ($request) {
                                    $query->limit($request->input('limit'));
                                })
                                ->get();

            return response()->json($pastActivities);
        } catch (Exception $e) {
            throw $e;
        }

    }
    public function badges(Request $request){
        try{
            $user = Auth::user();

            $badges = DB::table('user_badges as ub')
                        ->join('badges as b', 'ub.badge_id', '=', 'b.id')
                        ->where('ub.user_id', $user->id)
                        ->select('b.*')
                        ->when($request->has('limit'), function ($query) use ($request) {
                            $query->limit($request->input('limit'));
                        })
                        ->get();

            return response()->json($badges);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function favouriteBadges(Request $request){
        try{
            $user = Auth::user();

            $badges = DB::table('user_badges as ub')
                        ->join('badges as b', 'ub.badge_id', '=', 'b.id')
                        ->where('ub.user_id', $user->id)
                        ->where('ub.favourite', '!=', null)
                        ->orderBy('ub.favourite', 'asc')
                        ->select('b.*')
                        ->when($request->has('limit'), function ($query) use ($request) {
                            $query->limit($request->input('limit'));
                        })
                        ->get();

            return response()->json($badges);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function setFavouriteBadges(Request $request){
        
        try {
            $badge_id = $request->input('badge_id');

            $user = Auth::user();

            $badges = DB::table('user_badges as ub')
                        ->where('ub.user_id', $user->id)
                        ->where('ub.badge_id', $badge_id)
                        ->first();

            if ($badges->favourite == null) {
                $badges->update(['favourite' => 1]);
            } else {
                $badges->update(['favourite' => null]);
            }

            return response()->json($badges);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
