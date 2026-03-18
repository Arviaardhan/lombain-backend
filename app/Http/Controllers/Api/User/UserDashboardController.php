<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Tim yang dibuat (Leader) + Hitung total anggota (accepted) untuk setiap tim
        $myManagedTeams = Team::where('leader_id', $user->id)
            ->withCount([
                'users as member_count' => function ($q) {
                    $q->where('team_user.status', 'accepted');
                }
            ])
            ->latest()
            ->get();

        // Hitung total incoming requests untuk semua tim yang dipimpin dan akan muncul di Notifikasi
        $incomingRequestsCount = DB::table('team_user')
            ->join('teams', 'teams.id', '=', 'team_user.team_id')
            ->where('teams.leader_id', $user->id)
            ->where('team_user.status', 'pending')
            ->count();

        // Tim yang diikuti (Member) + pending request + info leader
        $myJoinedTeams = Team::whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('team_user.status', 'accepted')
                ->where('leader_id', '!=', $user->id);
        })
            ->with('leader:id,name,avatar')
            ->get();

        $mySentRequests = DB::table('team_user')
            ->join('teams', 'teams.id', '=', 'team_user.team_id')
            ->where('team_user.user_id', $user->id)
            ->where('team_user.status', 'pending')
            ->select('teams.name as team_name', 'teams.competition_name', 'team_user.created_at', 'team_user.id as request_id')
            ->get();

        $myInvites = DB::table('team_user')
            ->join('teams', 'teams.id', '=', 'team_user.team_id')
            ->where('team_user.user_id', $user->id)
            ->where('team_user.status', 'invited') // Ambil yang statusnya invited
            ->select('teams.name as team_name', 'team_user.id as invite_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'managed_teams' => $myManagedTeams,
                'joined_teams' => $myJoinedTeams,
                'sent_requests' => $mySentRequests,
                'invites' => $myInvites,
                'notifications' => [
                    'incoming_requests_total' => $incomingRequestsCount
                ]
            ]
        ]);
    }
}