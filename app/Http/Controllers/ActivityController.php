<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityParticipantRole;
use App\Models\ActivityParticipants;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    // Public
    public function get(Request $request) {
        try {
            $request->validate([
                'search_term' => 'string',
                'activity_type' => 'string|exists:activities,activity_type',
                'start_date' => 'date_format:Y-m-d H:i:s',
                'end_date' => 'date_format:Y-m-d H:i:s|after:start_date',
                'per_page' => 'integer',
            ]);

            $activities = DB::table('activities', 'a')
                            ->select([
                                'a.id',
                                'a.name',
                                'a.description',
                                'a.activity_type',
                                DB::raw("(
                                    SELECT STRING_AGG(ar.name, ', ')
                                    FROM activity_roles ar
                                    WHERE ar.group_id = a.role_group_id
                                ) as roles"),
                                DB::raw("(
                                    SELECT JSON_AGG(JSON_BUILD_OBJECT('id', b.id, 'name', b.name))
                                    FROM badges b
                                    WHERE b.activity_id = a.id
                                ) as badges"),
                                DB::raw("COUNT(par.user_id) AS participant_count"),
                                DB::raw("TO_CHAR(a.start_date, 'YYYY-mm-dd HH24:MI:SS') as start_date"),
                                DB::raw("TO_CHAR(a.end_date, 'YYYY-mm-dd HH24:MI:SS') as end_date"),
                                'a.slug',
                            ])
                            ->join('activity_participants as par', 'par.activity_id', '=', 'a.id')
                            ->when($request->has('search_term'), function ($query) use($request) {
                                $query->where('a.name', 'ILIKE', "%$request->search_term%");
                            })
                            ->when($request->has('activity_type'), function ($query) use($request) {
                                $query->whereIn('a.activity_type', $request->activity_type);
                            })
                            ->when($request->has('start_date'), function ($query) use($request) {
                                $query->where('a.start_date', '>=', $request->start_date);
                            })
                            ->when($request->has('end_date'), function ($query) use($request) {
                                $query->where('a.end_date', '<=', $request->end_date);
                            })
                            ->where('a.status', 1)
                            // ->where('a.start_date', '<=', Carbon::now())
                            // ->where('a.end_date', '>=', Carbon::now())
                            ->groupBy('a.id', 'a.name', 'a.description', 'a.activity_type', 'a.start_date', 'a.end_date', 'a.slug');

            $activities = $request->has('per_page') ? $activities->paginate($request->per_page) : $activities->get();

            return response()->json($activities);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getDetail(Request $request, int $id) {
        try {
            $activity = Activity::find($id);

            if (! isset($activity)) {
                return response()->json('Activity not found.', 404);
            }

            $activity = $activity->select([
                                    'a.id',
                                    'a.name',
                                    'a.description',
                                    'a.activity_type',
                                    DB::raw("(
                                        SELECT STRING_AGG(ar.name, ', ')
                                        FROM activity_roles ar
                                        WHERE ar.group_id = a.role_group_id
                                    ) as roles"),
                                    DB::raw("(
                                        SELECT JSON_AGG(JSON_BUILD_OBJECT('id', b.id, 'name', b.name))
                                        FROM badges b
                                        WHERE b.activity_id = a.id
                                    ) as badges"),
                                    DB::raw("COUNT(par.user_id) AS participant_count"),
                                    DB::raw("TO_CHAR(a.start_date, 'YYYY-mm-dd HH24:MI:SS') as start_date"),
                                    DB::raw("TO_CHAR(a.end_date, 'YYYY-mm-dd HH24:MI:SS') as end_date"),
                                    'a.slug',
                                ])
                                ->join('activity_participants as par', 'par.activity_id', '=', 'a.id')
                                ->where('activity.status', 1)
                                // ->where('activity.start_date', '<=', Carbon::now())
                                // ->where('activity.end_date', '>=', Carbon::now())
                                ->groupBy('a.id', 'a.name', 'a.description', 'a.activity_type', 'a.start_date', 'a.end_date', 'a.slug');

            $thumbnail = AssetController::getAsset($activity->id, Activity::class, 'Thumbnail');
            $details = AssetController::getAsset($activity->id, Activity::class, 'Details', false);

            return response()->json($activity);
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Auth
    public function enroll(Request $request, int $id) {
        try {
//            $request->validate([
//                'roles' => 'required|array'
//            ]);


            $user = Auth::user();
            $user_id = $user->id;

            $registration_id = ActivityParticipants::insertGetId([
                'user_id' => $user_id,
                'activity_id' => $id,
            ]);

//            foreach ($request->roles as $role_id) {
//                ActivityParticipantRole::create([
//                    'registration_id' => $registration_id,
//                    'role_id' => $role_id
//                ]);
//            }

            return response()->json($registration_id);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
