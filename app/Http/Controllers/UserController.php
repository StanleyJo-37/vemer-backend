<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function activitiesAttended(Request $request){
        $user_id = $request->user()->id;
        try {
            $events = DB::table('activity_participants')->where('user_id', $user_id)
                ->join('activities', 'activity_participants.activity_id', '=', 'activities.id')
                ->select('activities.id')
                ->count("activities.id");
            return response()->json($events);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getRank(Request $request){
        $user_id = $request->user()->id;

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

            $rankData = DB::selectOne($query, [$user_id]);

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
            return response()->json([
                'success' => false,
                'message' => 'An internal server error occurred while fetching the rank.'
            ], 500);
        }
    }

    public function getTotalPoints(Request $request){
        try{
            // $userid = $request->user()->id;
            $userid = 18;
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
        $user_id = $request->user()->id();
        try {
            $activities = DB::table('activities')->where('start_date', '>', now())
                ->join('activity_participants', 'activities.id', '=', 'activity_participants.activity_id')
                ->where('activity_participants.user_id', $user_id)
                ->select('activities.*')
                ->get();
            return response()->json($activities);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function announcements(Request $request){
        $user_id = $request->user()->id();
        try {
            $announcements = DB::table('notifications')
                ->join('user_notification', 'notifications.id', '=', 'user_notification.notification_id')
                ->where('user_notification.user_id', $user_id)
                ->select('notifications.*')
                ->get();
            return response()->json($announcements);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function recommendedActivities(Request $request){
        try {
            $activities = DB::table('activities')
                ->where('start_date', '>', now())
                ->orderBy('start_date', 'asc')
                ->limit(3)
                ->get();
            return response()->json($activities);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function recentParticipation(Request $request){
        $user_id = $request->user()->id();
        try{
            $pastActivities = DB::table('activity_participants')
                ->join('activities', 'activity_participants.activity_id', '=', 'activities.id')
                ->where('activity_participants.user_id', $user_id)
                ->where('activities.start_date', '<', now())
                ->orderBy('activities.start_date', 'desc')
                ->select('activities.*')
                ->get();
            return response()->json($pastActivities);
        } catch (Exception $e) {
            throw $e;
        }

    }
    public function badges(Request $request){
        $user_id = $request->user()->id();
        try{
            $badges = DB::table('user_badges')
                ->join('badges', 'user_badges.badge_id', '=', 'badges.id')
                ->where('user_badges.user_id', $user_id)
                ->select('badges.*')
                ->get();
            return response()->json($badges);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function favouriteBadges(Request $request){
        $user_id = $request->user()->id();
        try{
            $badges = DB::table('user_badges')
                ->join('badges', 'user_badges.badge_id', '=', 'badges.id')
                ->where('user_badges.user_id', $user_id)
                ->where('user_badges.favourite', '!=', null)
                ->orderBy('user_badges.favourite', 'asc')
                ->select('badges.*')
                ->get();
            return response()->json($badges);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function setFavouriteBadges(Request $request){
        $user_id = $request->user()->id();
        $badge_id = $request->badge_id;
        try{
            $badges = DB::table('user_badges')
                ->where('user_id', $user_id)
                ->where('badge_id', $badge_id)
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
