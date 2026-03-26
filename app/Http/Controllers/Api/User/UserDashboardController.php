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

        // 1. Tim yang dikelola (Leader) + Hitung Anggota + Load Detail Anggota
        $myManagedTeams = Team::where('leader_id', $user->id)
            ->withCount([
                'users as member_count' => function ($q) {
                    $q->where('team_user.status', 'accepted');
                }
            ])
            ->with([
                'users' => function ($q) {
                    $q->where('team_user.status', 'accepted')
                        ->select('users.id', 'users.name', 'users.avatar')
                        ->withPivot('role');
                }
            ])
            ->latest()
            ->get()
            ->map(function ($team) {
                // Leader dihitung +1
                $team->member_count = $team->member_count + 1;
                return $team;
            });

        $managedTeamIds = $myManagedTeams->pluck('id');

        // 2. Definisi variabel $incomingRequests (yang tadi error/unassigned)
        $incomingRequests = DB::table('team_user')
            ->join('teams', 'teams.id', '=', 'team_user.team_id')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->leftJoin('team_roles', 'team_roles.id', '=', 'team_user.role_id')
            ->whereIn('team_user.team_id', $managedTeamIds)
            ->where('team_user.status', 'pending')
            ->select(
                'team_user.id',
                'team_user.team_id',
                'users.name as user_name',
                'teams.name as team_name',
                'team_roles.role_name',
                'team_user.status',
                'team_user.note',
                'team_user.created_at'
            )
            ->get();

        // 3. Hitung total incoming requests untuk Badge Notifikasi
        $incomingRequestsCount = DB::table('team_user')
            ->join('teams', 'teams.id', '=', 'team_user.team_id')
            ->where('teams.leader_id', $user->id)
            ->where('team_user.status', 'pending')
            ->count();

        // 4. Tim yang diikuti (Member) + Load Rekan Setim
        $myJoinedTeams = Team::whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->where('team_user.status', 'accepted')
                ->where('leader_id', '!=', $user->id);
        })
            ->withCount([
                'users as member_count' => function ($q) {
                    $q->where('team_user.status', 'accepted');
                }
            ])
            ->with('leader:id,name,avatar')
            ->with([
                'users' => function ($q) {
                    $q->where('team_user.status', 'accepted')
                        ->select('users.id', 'users.name', 'users.avatar')
                        ->withPivot('role', 'status');
                }
            ])
            ->get()
            ->map(function ($team) {
                // Member lain + Dirinya + 1 Leader
                $team->member_count = $team->member_count + 1;
                return $team;
            });

        // 5. Permintaan yang sedang kita kirim ke tim lain
        $mySentRequests = DB::table('team_user')
            ->join('teams', 'teams.id', '=', 'team_user.team_id')
            ->where('team_user.user_id', $user->id)
            ->where('team_user.status', 'pending')
            ->select('teams.name as team_name', 'teams.competition_name', 'team_user.created_at', 'team_user.id as request_id')
            ->get();

        // 6. Undangan dari tim lain untuk kita
        $myInvites = DB::table('team_user')
            ->join('teams', 'teams.id', '=', 'team_user.team_id')
            ->join('users', 'users.id', '=', 'teams.leader_id')
            ->where('team_user.user_id', $user->id)
            ->where('team_user.status', 'invited')
            ->select(
                'team_user.id',
                'teams.name as team_name',
                'users.name as leader_name', // Ambil nama leader
                'team_user.created_at'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'managed_teams' => $myManagedTeams,
                'incoming_requests' => $incomingRequests,
                'joined_teams' => $myJoinedTeams,
                'sent_requests' => $mySentRequests,
                'invites' => $myInvites,
                'notifications' => $user->notifications()->latest()->take(15)->get(),

                // Metadata tambahan jika butuh count
                'unread_notifications_count' => $user->unreadNotifications()->count(),
            ]
        ]);
    }
}