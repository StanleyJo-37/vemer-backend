<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AssetController;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PublisherController extends Controller
{
    public function totalActivities(Request $request){
        $user_id = $request->user()->id;
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
                    ->where('roles.name', 'publisher')
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
        $user_id = $request->user()->id;
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
                    ->where('roles.name', 'publisher')
                    ->distinct()
                    ->pluck('activity_participants.activity_id');

                if ($publishedActivityIds->isNotEmpty()) {
                    $totalParticipants = DB::table('activity_participants')
                        ->whereIn('activity_id', $publishedActivityIds)
                        ->distinct()
                        ->count('user_id');
                    return response()->json($totalParticipants);
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function totalNotifications(Request $request){
        $user_id = $request->user()->id;
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
                    ->where('roles.name', 'publisher')
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
        $user_id = $request->user()->id;
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
                    ->where('roles.name', 'publisher')
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
        try {
            // di tabel activities perlu ditambahin bbrp column lagi, seperti about, what_will_you_get, location, point_reward
            // Validate the request
            $request->validate([
                'title' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'about' => 'required|string',
                'what_will_you_get' => 'required|string',
                'category' => 'required|string',
                'time' => 'required|string',
                'date' => 'required|date',
                'location' => 'required|string',
                'point_reward' => 'required|integer|min:0',
            ]);

            // Get the user ID
            $user_id = $request->user()->id;

            // Check if the user is a publisher
            $isPublisher = DB::table('users')
                ->where('id', $user_id)
                ->where('is_publisher', true)
                ->exists();

            if (!$isPublisher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only publishers can create activities.'
                ], 403);
            }

            // Create a slug from the title
            $slug = Str::slug($request->title);

            // Format the date and time
            $start_date = Carbon::parse($request->date . ' ' . $request->time);

            // Create the activity
            $activity = DB::table('activities')->insertGetId([
                'name' => $request->title,
                'slug' => $slug,
                'description' => $request->about,
                'what_will_you_get' => $request->what_will_you_get,
                'category' => $request->category,
                'location' => $request->location,
                'point_reward' => $request->point_reward,
                'start_date' => $start_date,
                'end_date' => $start_date->copy()->addHours(2), // Default to 2 hours duration
                'status' => 1, // Active
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Register the user as a publisher for this activity
            $registration_id = DB::table('activity_participants')->insertGetId([
                'user_id' => $user_id,
                'activity_id' => $activity,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get the publisher role ID
            $publisher_role = DB::table('roles')
                ->where('name', 'publisher')
                ->first();

            if ($publisher_role) {
                DB::table('activity_participant_roles')->insert([
                    'registration_id' => $registration_id,
                    'role_id' => $publisher_role->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Upload the image
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                AssetController::uploadAsset(
                    $image,
                    'activities',
                    $activity,
                    'App\\Models\\Activity',
                    'Thumbnail'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Activity created successfully',
                'data' => [
                    'activity_id' => $activity
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function uploadImage(Request $request){
        try {
            // Validate the request
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'folder' => 'required|string',
            ]);

            // Get the user ID
            $user_id = $request->user()->id;

            // Check if the user is a publisher
            $isPublisher = DB::table('users')
                ->where('id', $user_id)
                ->where('is_publisher', true)
                ->exists();

            if (!$isPublisher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only publishers can upload images.'
                ], 403);
            }

            // Get the image from the request
            $image = $request->file('image');

            // Generate a unique filename
            $filename = time() . '_' . $image->getClientOriginalName();

            // Define the path where the image will be stored
            $folder = $request->input('folder');
            $path = $folder . '/' . $filename;

            // Store the image using the S3 disk (Supabase)
            $url = $image->storeAs($folder, $filename, 's3');

            // Get the full URL of the uploaded image
            $fullUrl = config('app.url') . '/storage/' . $url;

            // Return the response with the image URL
            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => [
                    'url' => $url,
                    'full_url' => $fullUrl
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getNotifications(Request $request){
        $user_id = $request->user()->id;
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
                    ->where('roles.name', 'publisher')
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
    public function createBadge(Request $request){
        try {
            // Validate the request
            $request->validate([
                'activity_id' => 'required|integer|exists:activities,id',
                'name' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'description' => 'required|string',
            ]);

            // Get the user ID
            $user_id = $request->user()->id;

            // Check if the user is a publisher
            $isPublisher = DB::table('users')
                ->where('id', $user_id)
                ->where('is_publisher', true)
                ->exists();

            if (!$isPublisher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only publishers can create badges.'
                ], 403);
            }

            // Check if the user is a publisher for this activity
            $isActivityPublisher = DB::table('activity_participants')
                ->join('activity_participant_roles', 'activity_participants.id', '=', 'activity_participant_roles.registration_id')
                ->join('roles', 'activity_participant_roles.role_id', '=', 'roles.id')
                ->where('activity_participants.user_id', $user_id)
                ->where('activity_participants.activity_id', $request->activity_id)
                ->where('roles.name', 'publisher')
                ->exists();

            if (!$isActivityPublisher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You are not a publisher for this activity.'
                ], 403);
            }

            // Create the badge
            $badge = DB::table('badges')->insertGetId([
                'name' => $request->name,
                'description' => $request->description,
                'activity_id' => $request->activity_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Upload the image
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                AssetController::uploadAsset(
                    $image,
                    'badges',
                    $badge,
                    'App\\Models\\Badge',
                    'Badge'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Badge created successfully',
                'data' => [
                    'badge_id' => $badge
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create badge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approveParticipants(Request $request){
        $user_id = $request->user_id;
        $activity_id = $request->activity_id;
        $status = $request->status;
        try {
            // tolong ditambahin attribute status ke tabel activity_participants
            $query = DB::table("activity_participants")
                ->where("user_id", $user_id)
                ->where("activity_id", $activity_id)
                ->update(["status" => $status]);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
