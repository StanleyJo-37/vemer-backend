<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublisherController extends Controller
{
    public function totalActivities(Request $request){
        $user_id = request()->user()->id;
        try{
            $isPublisher = DB::table('users')
                ->where('id', $user_id)
                ->where('is_publisher', true)
                ->exists();

            if ($isPublisher) {
                $totalActivities = DB::table('activities')
                    ->join('activity_participants', 'activities.id', '=', 'activity_participants.activity_id')
                    ->join('activity_participant_roles', 'activity_participants.id', '=', 'activity_participant_roles.registration_id')
                    ->join('roles', 'activity_participant_roles.role_id', '=', 'roles.id')
                    ->where('activity_participants.user_id', $user_id)
                    ->where('roles.name', 'Publisher')
                    ->distinct()
                    ->count('activities.id');

                return response()->json($totalActivities);
            } else {
                // This part is correct
                return response()->json(0);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function totalParticipants(Request $request){
        $user_id = request()->user()->id;
        try{
            $isPublisher = DB::table('users')
                ->where('id', $user_id)
                ->where('is_publisher', true)
                ->exists();

            if ($isPublisher) {
                $publishedActivityIds = DB::table('activity_participants')
                    ->join('activity_participant_roles', 'activity_participants.id', '=', 'activity_participant_roles.registration_id')
                    ->join('roles', 'activity_participant_roles.role_id', '=', 'roles.id')
                    ->where('activity_participants.user_id', $user_id)
                    ->where('roles.name', 'Publisher')
                    ->distinct()
                    ->pluck('activity_participants.activity_id');

                if ($publishedActivityIds->isNotEmpty()) {
                    $totalParticipants = DB::table('activity_participants')
                        ->whereIn('activity_id', $publishedActivityIds)
                        ->distinct()
                        ->count('user_id');
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function totalNotifications(Request $request){
        $user_id = request()->user()->id;
        try{
            $isPublisher = DB::table('users')
                ->where('id', $user_id)
                ->where('is_publisher', true)
                ->exists();

            if ($isPublisher) {
                $publishedActivityIds = DB::table('activity_participants')
                    ->join('activity_participant_roles', 'activity_participants.id', '=', 'activity_participant_roles.registration_id')
                    ->join('roles', 'activity_participant_roles.role_id', '=', 'roles.id')
                    ->where('activity_participants.user_id', $user_id)
                    ->where('roles.name', 'Publisher')
                    ->distinct()
                    ->pluck('activity_participants.activity_id');

                $notificationCount = 0;

                if ($publishedActivityIds->isNotEmpty()) {
                    $notificationCount = DB::table('activity_notification')
                        ->whereIn('activity_id', $publishedActivityIds)
                        ->count();
                }

                return response()->json($notificationCount);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getActivities(Request $request){
        $user_id = request()->user()->id;
        try{
            $isPublisher = DB::table('users')
                ->where('id', $user_id)
                ->where('is_publisher', true)
                ->exists();

            if ($isPublisher) {
                $publishedActivityIds = DB::table('activity_participants')
                    ->join('activity_participant_roles', 'activity_participants.id', '=', 'activity_participant_roles.registration_id')
                    ->join('roles', 'activity_participant_roles.role_id', '=', 'roles.id')
                    ->where('activity_participants.user_id', $user_id)
                    ->where('roles.name', 'Publisher')
                    ->distinct()
                    ->pluck('activity_participants.activity_id');

                if ($publishedActivityIds->isNotEmpty()) {
                    $activities = DB::table('activities')
                        ->whereIn('id', $publishedActivityIds);

                    return response()->json($activities);
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function createActivity(Request $request){

    }
    public function uploadImage(Request $request){}
    public function getNotifications(Request $request){
        $user_id = request()->user()->id;
        try{
            $isPublisher = DB::table('users')
                ->where('id', $user_id)
                ->where('is_publisher', true)
                ->exists();

            if ($isPublisher) {
                $publishedActivityIds = DB::table('activity_participants')
                    ->join('activity_participant_roles', 'activity_participants.id', '=', 'activity_participant_roles.registration_id')
                    ->join('roles', 'activity_participant_roles.role_id', '=', 'roles.id')
                    ->where('activity_participants.user_id', $user_id)
                    ->where('roles.name', 'Publisher')
                    ->distinct()
                    ->pluck('activity_participants.activity_id');

                if ($publishedActivityIds->isNotEmpty()) {
                    $notifications = DB::table('activity_notification')
                        ->whereIn('activity_id', $publishedActivityIds)
                        ->join('notifications', 'activity_notification.notification_id', '=', 'notifications.id')
                        ->select('activity_notification.*', 'activities.title', '', 'activities.start_date', 'notifications.title', 'notifications.content');

                    return response()->json($notifications);
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function approveParticipants(Request $request){}
}
