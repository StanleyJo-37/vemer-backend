<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function getStatus(Request $request, $id){
        try{
            $user_id = $request->user()->id;
            Log::info("Activity id: " . $id . " " . "User id: " . $user_id);
            $status = DB::table('activity_participants')
                ->where('user_id', $user_id)
                ->where('activity_id', $id)
                ->value('status');

            if($status == null){
                return response()->json("Unregistered");
            }else {
                return response()->json($status);
            }

        } catch (Exception $e){
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

    public function getTotalPoints(Request $request){
        try{
            $userid = $request->user()->id;
            // $userid = 18;
            $userPoints = DB::table('user_points')
            ->where('user_id', $userid)
            ->whereIn('id', function ($query) use ($userid) {
                $query->select(DB::raw('MIN(id)'))
                ->from('user_points')
                ->where('user_id', $userid)
                ->groupBy('activity_id');
            })
            ->sum('point');

            Log::info("Check user points amount : " . $userPoints);

            return response()->json($userPoints);
        } catch (Exception $e){
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

            $page_count = (int) $request->input('per_page', 15);

            if($request->has('limit')){
                $page_count = (int) $request->input('limit');

                if($page_count <= 0){
                    $page_count = 4;
                }
            }

            $badges = DB::table('user_badges as ub')
                ->join('badges as b', 'ub.badge_id', '=', 'b.id')
                ->join('activities as a', 'ub.activity_id', '=', 'a.id')
                ->join('model_has_category as mhc', function ($join) {
                    $join->on('mhc.model_id', '=', 'a.id')
                            ->where('mhc.model_type', '=', 'Activity::class');
                })
                ->join('categories as c', 'mhc.category_id', '=', 'c.id')
                ->where('ub.user_id', $user->id)
                ->select('b.name as badge_name', 'b.description', 'c.name as category_name', 'ub.created_at as created_at', 'ub.favourite as favourite')
                ->paginate($page_count);

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
