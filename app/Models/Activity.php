<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Support\Facades\DB;

class Activity extends Model
{
    //
    protected $table = 'activities';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'activity_type',
        'role_group_id',
        'start_date',
        'end_date',
        'status',
    ];

    // public function users(): HasOneOrManyThrough {
    //     return $this->hasManyThrough(
    //         User::class,
    //         ActivityParticipants::class,
    //         'user_id',
    //         'id',
    //         'id',
    //         'activity_id',
    //     );
    // }

    public function getParticipants(int $activity_id) {

        $publishers = DB::table('users', 'u')
                            ->select([
                                'u.*'
                            ])
                            ->leftJoin('activity_participants as ap', 'ap.user_id', '=', 'u.id')
                            ->leftJoin('activities as a', 'a.id', '=', 'ap.activity_id')
                            ->where([
                                ['a.id', $activity_id],
                                ['u.is_publisher', true],
                            ])
                            ->get();
        
        $participants = DB::table('users', 'u')
                            ->select([
                                'u.*'
                            ])
                            ->leftJoin('activity_participants as ap', 'ap.user_id', '=', 'u.id')
                            ->leftJoin('activities as a', 'a.id', '=', 'ap.activity_id')
                            ->where([
                                ['a.id', $activity_id],
                                ['u.is_publisher', false],
                            ])
                            ->get();
        
        return [
            'publishers' => $publishers,
            'participants' => $participants,
        ];
    }

    // public function getBadges(int $activity_id) {
        
    //     $publishers = DB::table('users', 'u')
    //                         ->select([
    //                             'u.*'
    //                         ])
    //                         ->leftJoin('activity_participants as ap', 'ap.user_id', '=', 'u.id')
    //                         ->leftJoin('activities as a', 'a.id', '=', 'ap.activity_id')
    //                         ->where([
    //                             ['a.id', $activity_id],
    //                             ['u.is_publisher', true],
    //                         ])
    //                         ->get();
        
    //     $participants = DB::table('users', 'u')
    //                         ->select([
    //                             'u.*'
    //                         ])
    //                         ->leftJoin('activity_participants as ap', 'ap.user_id', '=', 'u.id')
    //                         ->leftJoin('activities as a', 'a.id', '=', 'ap.activity_id')
    //                         ->where([
    //                             ['a.id', $activity_id],
    //                             ['u.is_publisher', false],
    //                         ])
    //                         ->get();
        
    //     return [
    //         'publishers' => $publishers,
    //         'participants' => $participants,
    //     ];
    // }
}
