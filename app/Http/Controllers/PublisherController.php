<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AssetController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Activity;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\table;
use function PHPSTORM_META\type;

class PublisherController extends Controller
{
    public function totalActivities(Request $request){
        try{
            if (!Auth::check()) {
                Log::info('User not authenticated in totalActivities');
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $user_id = Auth::id();
            Log::info('User ID in totalActivities: ' . $user_id);

            $isPublisher = DB::table('users')
                ->where('id', $user_id)
                ->where('is_publisher', true)
                ->exists();

            if ($isPublisher) {
                $totalActivities = DB::table('activities')
                    ->join('activity_participants', 'activities.id', '=', 'activity_participants.activity_id')
                    // ->join('activity_participant_roles', 'activity_participants.id', '=', 'activity_participant_roles.registration_id')
                    // ->join('roles', 'activity_participant_roles.role_id', '=', 'roles.id')
                    ->where('activity_participants.user_id', $user_id)
                    // ->where('roles.name', 'publisher')
                    ->distinct()
                    ->count('activities.id');

                return response()->json($totalActivities);
            } else {
                // This part is correct
                return response()->json(0);
            }
        } catch (Exception $e) {
            Log::error('Error in totalActivities: ' . $e->getMessage());
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

    public function getIsPublisher(Request $request){
        try{
            Log::info('Starting getIsPublisher method');

            if (!Auth::check()) {
                Log::info('User not authenticated');
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $user_id = Auth::id();
            Log::info('User ID: ' . $user_id);

            $ispublisher = DB::table('users')->where('id', $user_id)
            ->first('is_publisher');

            Log::info('Is publisher result: ' . json_encode($ispublisher));

            if($ispublisher == null){
                return response()->json(['message' => 'User not found'], 404);
            }

            return response()->json($ispublisher);
        } catch (Exception $e){
            Log::error('Error in getIsPublisher: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createActivity(Request $request){
        try {
            // di tabel activities perlu ditambahin bbrp column lagi, seperti location, point_reward
            // Validate the request
            $request->validate([
                'title' => 'required|string|max:255',
                'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048|nullable|required',
                'description' => 'required|string',
                'category' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
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
            $start_date = Carbon::parse($request->start_date . ' ' . $request->time);
            $end_date = Carbon::parse($request->end_date . ' ' . $request->time);

            // Create the activity
            $activity = DB::table('activities')->insertGetId([
                'name' => $request->title,
                'slug' => $slug,
                'description' => $request->description,
                'category' => $request->category,
                'location' => $request->location,
                'point_reward' => $request->point_reward,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => 1, // Active
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Register the user as a publisher for this activity
            $registration_id = DB::table('activity_participants')->insertGetId([
                'user_id' => $user_id,
                'activity_id' => $activity,
                'status' => 'Confirmed',
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
                    Activity::class,
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

    public function createRegistrationPopupInfo(Request $request)
    {

        try {
            $request->validate([
                'activity_id' => 'required|integer|exists:activities,id', // Must be a valid ID in the activities table
                'title' => 'required|string|max:255',
                'description' => 'required|string',
            ]);

            $popupInfoId = DB::table('registration_popup_info')->insertGetId([
                'activity_id' => $request->input('activity_id'),
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'created_at' => now(), // Manually set timestamps
                'updated_at' => now(),
            ]);

            // Retrieve the full record we just created to return in the response
            $popupInfo = DB::table('registration_popup_info')->find($popupInfoId);

            // If successful, return a success response with the created data.
            return response()->json([
                'success' => true,
                'message' => 'Registration popup info created successfully.',
                'data' => $popupInfo
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // If something goes wrong during database creation, return a server error.
            // It's a good practice to log the error for debugging.
            // Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the record.',
                'error_details' => $e->getMessage()
            ], 500); // 500 Internal Server Error
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
                'icon' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            if ($request->hasFile('icon')) {
                $image = $request->file('icon');
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

    public function createActivityWithBadgeAndPopup(Request $request)
    {
        // Start a database transaction to ensure atomicity
        DB::beginTransaction();


        try {
            // Using 'required_if' makes the validation conditional based on the boolean flags.

            $validatedData = $request;
            $validatedData = $request->validate([
                // --- Activity Fields (Always required) ---
                'title' => 'required|string|max:255',
                'activity_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'activity_description' => 'required|string',
                'category' => 'required|string',
                'start_date' => 'required|date_format:Y-m-d\TH:i',
                'end_date'   => 'required|date_format:Y-m-d\TH:i|after_or_equal:start_date',
                'location' => 'required|string',
                'point_reward' => 'required|integer|min:0',

                // // --- Boolean Flags ---
                'badge_exist' => 'required|boolean',
                'popup_exist' => 'required|boolean',

                // // --- Badge Fields (Conditionally required) ---
                'name' => 'required_if:badge_exist,true|string|max:255',
                'icon' => 'required_if:badge_exist,true|image|mimes:jpeg,png,jpg,gif|max:2048',
                'badge_description' => 'required_if:badge_exist,true|string',

                // // --- Popup Fields (Conditionally required) ---
                'popup_title' => 'required_if:popup_exist,true|string|max:255',
                'popup_description' => 'required_if:popup_exist,true|string',
            ]);

            // You can also use logger('message') or info('message') helper functions


            // 2. Get user ID and check if they are a publisher

            $user_id = $request->user()->id;


            $isPublisher = DB::table('users')->where('id', $user_id)->where('is_publisher', true)->exists();


            if (!$isPublisher) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Unauthorized. Only publishers can perform this action.'], 403);
            }

            // 3. Create the Activity
            $slug = Str::slug($validatedData['title']);
            $start_datetime = Carbon::createFromFormat('Y-m-d\TH:i', $validatedData['start_date']);
            $end_datetime = Carbon::createFromFormat('Y-m-d\TH:i', $validatedData['end_date']);


            $activity_id = DB::table('activities')->insertGetId([
                'name' => $validatedData['title'],
                'slug' => $slug,
                'description' => $validatedData['activity_description'],
                'activity_type' => "volunteer",
                'role_group_id' => 1,
                'location' => $validatedData['location'],
                'points_reward' => $validatedData['point_reward'],
                'start_date' => $start_datetime,
                'end_date' => $end_datetime,
                'status' => true, // Active
                'created_at' => now(),
                'updated_at' => now(),
            ]);


            // 4. Register the creator as a publisher for this activity
            // This logic appears consistent with your example.
            DB::table('activity_participants')->insertGetId([
                'user_id' => $user_id, 'activity_id' => $activity_id, 'status' => 'Pending',
                'created_at' => now(), 'updated_at' => now(),
            ]);


            // 5. Upload Activity Image (if provided)
            if ($request->hasFile('activity_image')) {
                AssetController::uploadAsset($request->file('activity_image'), 'activities', $activity_id, 'App\\Models\\Activity', 'Thumbnail');
            }

            // This will hold the IDs of the items we create
            $createdItems = ['activity_id' => $activity_id];

            // 6. Create Badge (if requested)
            if ($validatedData['badge_exist']) {
                $badge_id = DB::table('badges')->insertGetId([
                    'name' => $validatedData['name'],
                    'description' => $validatedData['badge_description'],
                    'activity_id' => $activity_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $createdItems['badge_id'] = $badge_id;

                // Upload badge icon
                if ($request->hasFile('badge_icon')) {
                    AssetController::uploadAsset($request->file('badge_icon'), 'badges', $badge_id, 'App\\Models\\Badge', 'Badge');
                }
            }

            // 7. NEW: Create Registration Popup Info (if requested)
            if ($validatedData['popup_exist']) {
                $popup_info_id = DB::table('registration_popup_info')->insertGetId([
                    'activity_id' => $activity_id,
                    'title' => $validatedData['popup_title'],
                    'description' => $validatedData['popup_description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $createdItems['popup_info_id'] = $popup_info_id;
            }

            // Commit the transaction as all operations were successful
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Activity and related items created successfully.',
                'data' => $createdItems
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            // In a real application, you would log the error.
            // Log::error("Error creating activity bundle: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred.', 'error_details' => $e->getMessage()], 500);
        }
    }

    public function changeParticipantStatus(Request $request){
        try {
            if (!Auth::check()) {
                Log::info('User not authenticated in changeParticipant');
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // Validate the request
            $validated = $request->validate([
                'user_id' => 'required|integer',
                'activity_id' => 'required|integer',
                'status' => 'required|string'
            ]);

            $user_id = $validated['user_id'];
            $activity_id = $validated['activity_id'];
            $status = $validated['status'];

            Log::info('Approving participant: User ID: ' . $user_id . ', Activity ID: ' . $activity_id . ', Status: ' . $status);

            $query = DB::table("activity_participants")
                ->where("user_id", $user_id)
                ->where("activity_id", $activity_id)
                ->update(["status" => $status]);

            return response()->json(['message' => 'Participant status updated successfully']);
        } catch (Exception $e) {
            Log::error('Error in approveParticipants: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getActivityParticipants(Request $request, int $id){
        try {
            if (!Auth::check()) {
                Log::info('User not authenticated in getParticipants');
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $publisher_id = Auth::id();

            Log::info('User ID in getParticipants: ' . $publisher_id);
            $participants = DB::table('activity_participants')
                ->join("users", "activity_participants.user_id", "=", "users.id")
                ->where("activity_participants.activity_id", $id)
                ->select("users.*", "activity_participants.status")
                ->get();

            $isPublisherInList = $participants->contains('id', $publisher_id);

            if ($isPublisherInList) {
                Log::info("Publisher ($publisher_id) was found in the participant list for activity $id.");
                $participantsWithoutPublisher = $participants->where('id', '!=', $publisher_id)
                    ->select('id', 'name', 'email', 'status');
                return response()->json($participantsWithoutPublisher);
            } else {
                // The publisher's ID was NOT found among the participants
                Log::info("Publisher ($publisher_id) was NOT in the participant list for activity $id.");
                return response()->json([
                    'message' => 'Not a publisher.'
                ], 404);
            }
        } catch (Exception $e) {
            Log::error('Error in getParticipants: ' . $e->getMessage());
            throw $e;
        }
    }
    public function getAllActivites(Request $request){
        try {
            if (!Auth::check()) {
                Log::info('User not authenticated in getAllActivites');
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $user_id = Auth::id();
            Log::info('User ID in getAllActivites: ' . $user_id);

            $activities = DB::table('activities')
                ->join("activity_participants", "activities.id", "=", "activity_participants.activity_id")
                ->where("activity_participants.user_id", $user_id)
                ->select("activities.*")
                ->get();

            return response()->json($activities->isEmpty() ? [] : $activities);
        } catch (Exception $e) {
            Log::error('Error in getAllActivites: ' . $e->getMessage());
            throw $e;
        }
    }

    public function endActivity(Request $request, int $id) {
        try {
            if (!Auth::check()) {
                Log::info('User not authenticated in getParticipants');
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $publisher_id = Auth::id();

            Log::info('User ID in getParticipants: ' . $publisher_id);
            $participants = DB::table('activity_participants')
                ->join("users", "activity_participants.user_id", "=", "users.id")
                ->where("activity_participants.activity_id", $id)
                ->select("users.*", "activity_participants.status")
                ->get();

            $isPublisherInList = $participants->contains('id', $publisher_id);

            if ($isPublisherInList) {
                Log::info("Publisher ($publisher_id) was found in the participant list for activity $id.");
                $participantsWithoutPublisher = $participants->where('id', '!=', $publisher_id);
                $activity = DB::table('activities')->where('id', $id)->first();
                $badge = DB::table('badges')->where('activity_id', $id)->first();

                foreach ($participantsWithoutPublisher as $participant) {
                    $participantId = $participant->id;

                    if ($participant->status == 'Confirmed') {
                        DB::table('user_points')->insert([
                            'user_id' => $participantId,
                            'leaderboard_id' => 0,
                            'point' => $activity->points_reward,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'activity_id' => $id
                        ]);

                        if ($badge != null) {
                            $badges = [
                                'user_id' => $participantId,
                                'badge_id' => $badge->id,
                                'points_awarded' => 10,
                                'created_at' => now(),
                                'updated_at' => now(),
                                'favourite' => 10
                            ];

                            DB::table('user_badges')->insert([$badges]);
                        }
                    }
                }

                DB::table('activities')
                    ->where('id', $id)
                    ->update(['status' => false]);

                return response()->json([
                    'message' => 'Activity ended successfully',
                    'data' => $activity,
                ], 200);
            } else {
                // The publisher's ID was NOT found among the participants
                Log::info("Publisher ($publisher_id) was NOT in the participant list for activity $id.");
                return response()->json([
                    'message' => 'Not a publisher.'
                ], 404);
            }
        } catch (Exception $e) {
            Log::error('Error in getParticipants: ' . $e->getMessage());
            throw $e;
        }
    }
}
