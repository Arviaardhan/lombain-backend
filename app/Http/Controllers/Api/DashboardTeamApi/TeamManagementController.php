<?php

namespace App\Http\Controllers\Api\DashboardTeamApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\UserJoinedTeam;
use App\Models\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamStatusNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TeamManagementController extends Controller
{
    // LIST JOIN REQUESTS
    public function requests($teamId)
    {
        $requests = DB::table('team_user')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->where('team_user.team_id', $teamId)
            ->whereIn('team_user.status', ['pending', 'invited'])
            ->select('users.id', 'users.name', 'users.major', 'team_user.id as request_id', 'team_user.status', 'team_user.note')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function invite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|integer|exists:teams,id',
            'user_id' => 'required|integer|exists:users,id',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors()
            ], 400);
        }

        $team = Team::where('id', $request->team_id)
            ->where('leader_id', auth()->id())
            ->first();

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya leader yang bisa mengundang'
            ], 403);
        }

        $existing = DB::table('team_user')
            ->where('team_id', $request->team_id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($existing && in_array($existing->status, ['invited', 'pending', 'accepted', 'assigned'])) {
            return response()->json([
                'success' => false,
                'message' => 'User sudah ada di tim atau sedang diproses.'
            ], 400);
        }

        if ($existing && $existing->status === 'rejected') {
            // UPDATE jika sebelumnya pernah direject
            DB::table('team_user')
                ->where('id', $existing->id)
                ->update([
                    'status' => 'invited',
                    'note' => $request->note,
                    'updated_at' => now(),
                ]);
        } else {
            // INSERT jika benar-benar baru
            DB::table('team_user')->insert([
                'team_id' => $request->team_id,
                'user_id' => $request->user_id,
                'status' => 'invited',
                'note' => $request->note,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // --- PROSES NOTIFIKASI ---
        $targetUser = User::find($request->user_id);
        if ($targetUser) {
            try {
                // Siapkan detail notifikasi
                $details = [
                    'subject' => 'Undangan Bergabung Tim 🚀',
                    'message' => 'Kamu diundang oleh ' . auth()->user()->name . ' untuk bergabung ke tim ' . $team->name . '.',
                    'action_url' => url('/dashboard/invitations'),
                    'team_id' => $team->id
                ];

                // Kirim Notifikasi (Email + DB)
                $targetUser->notify(new TeamStatusNotification($details));

            } catch (\Exception $e) {
                // Jika email gagal/timeout, tetap biarkan database sukses agar user tidak melihat error 500
                Log::error("Email Notification Error: " . $e->getMessage());
            }
        }

        // Broadcast Event untuk Real-time (Jika ada)
        if (class_exists(UserJoinedTeam::class)) {
            event(new UserJoinedTeam("Kamu diundang bergabung ke tim: " . $team->name, $request->user_id));
        }

        // WAJIB RETURN JSON DI SINI
        return response()->json([
            'success' => true,
            'message' => 'Undangan berhasil dikirim!'
        ], 200);
    }

    public function respondInvite(Request $request)
    {
        $request->validate([
            'invite_id' => 'required|exists:team_user,id',
            'action' => 'required|in:accept,reject'
        ]);

        $invite = Team::join('team_user', 'teams.id', '=', 'team_user.team_id')
            ->where('team_user.id', $request->invite_id)
            ->select('teams.*', 'team_user.user_id as applicant_id')
            ->first();

        if (!$invite)
            return response()->json(['message' => 'Not Found'], 404);

        // ✅ FIX DI SINI
        $status = $request->action === 'accept' ? 'assigned' : 'rejected';

        DB::table('team_user')
            ->where('id', $request->invite_id)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);

        $leader = User::find($invite->leader_id);
        $userName = auth()->user()->name;

        if ($leader) {
            $details = [
                'team_id' => $invite->id,
                'subject' => $status === 'assigned'
                    ? 'Undangan Diterima! 🎉'
                    : 'Undangan Ditolak ❌',
                'message' => "User {$userName} telah " .
                    ($status === 'assigned'
                        ? 'bergabung dengan'
                        : 'menolak undangan') .
                    " tim {$invite->name}.",
                'action_url' => url('/dashboard'),
            ];

            $leader->notify(new TeamStatusNotification($details));
        }

        return response()->json(['success' => true]);
    }

    public function assignRole(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:team_roles,id',
        ]);

        DB::table('team_user')
            ->where('team_id', $request->team_id)
            ->where('user_id', $request->user_id)
            ->update([
                'role_id' => $request->role_id,
                'status' => 'assigned'
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Role assigned'
        ]);
    }

    public function teamStructure($teamId)
    {
        $team = DB::table('teams')
            ->where('id', $teamId)
            ->where('leader_id', auth()->id())
            ->first();

        if (!$team) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $roles = TeamRole::where('team_id', $teamId)
            ->with([
                'users' => function ($q) use ($teamId) {
                    $q->where('team_user.team_id', $teamId)
                        ->whereIn('team_user.status', ['accepted', 'assigned', 'invited']);
                }
            ])
            ->with('skills')
            ->get();

        // ✅ UNASSIGNED (Waiting List)
        $unassigned = DB::table('team_user')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->where('team_user.team_id', $teamId)
            ->whereNull('team_user.role_id')
            ->whereIn('team_user.status', ['accepted', 'assigned'])
            // 🔥 TAMBAHKAN BARIS INI: Kecualikan sang Leader
            ->where('users.id', '!=', $team->leader_id)
            ->select(
                'team_user.id as id',
                'users.id as user_id',
                'users.name',
                'users.avatar'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'role_name' => $role->role_name,
                        'max_slot' => $role->max_slot,
                        'filled' => $role->users->where('pivot.status', 'accepted')->count(),
                        'members' => $role->users->map(function ($u) {
                            return [
                                'id' => $u->id, // User ID
                                'pivot_id' => $u->pivot->id, // Simpan pivot ID juga jika perlu
                                'name' => $u->name,
                                'avatar' => $u->avatar,
                                'status' => $u->pivot->status
                            ];
                        }),
                        'required_skills' => $role->skills->pluck('skill_name'),
                    ];
                }),
                'unassigned' => $unassigned
            ]
        ]);
    }

    public function swapRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'team_id' => 'required|exists:teams,id',
            'new_role_id' => 'required|exists:team_roles,id'
        ]);

        try {
            DB::transaction(function () use ($request) {
                $team = DB::table('teams')
                    ->where('id', $request->team_id)
                    ->where('leader_id', auth()->id())
                    ->first();

                if (!$team)
                    throw new \Exception('Unauthorized');

                $usedSlot = DB::table('team_user')
                    ->where('team_id', $request->team_id)
                    ->where('role_id', $request->new_role_id)
                    ->whereIn('status', ['accepted', 'assigned'])
                    ->count();

                $role = DB::table('team_roles')->where('id', $request->new_role_id)->first();

                if ($usedSlot >= $role->max_slot) {
                    throw new \Exception('Role tujuan sudah penuh');
                }

                DB::table('team_user')
                    ->where('id', $request->team_id)
                    ->where('user_id', $request->user_id)
                    ->update([
                        'role_id' => $request->new_role_id,
                        'updated_at' => now()
                    ]);
            });

            return response()->json(['success' => true, 'message' => 'Role berhasil dipindahkan']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function reject($requestId)
    {
        $join = DB::table('team_user')->where('id', $requestId)->first();
        $team = Team::find($join->team_id);

        DB::table('team_user')
            ->where('id', $requestId)
            ->update([
                'status' => 'rejected',
                'updated_at' => now()
            ]);

        // --- NOTIFIKASI REJECT ---
        $targetUser = User::find($join->user_id);
        if ($targetUser) {
            try {
                $targetUser->notify(new TeamStatusNotification([
                    'subject' => 'Update Status Lamaran Tim 📋',
                    'message' => 'Maaf, lamaran kamu untuk tim ' . $team->name . ' belum bisa diterima saat ini. Tetap semangat!',
                    'action_url' => url('/explore/teams'),
                    'team_id' => $team->id
                ]));
            } catch (\Exception $e) {
                Log::error("Reject Notification Error: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'message' => 'Request ditolak']);
    }

    public function removeMember($teamId, $userId)
    {
        $team = DB::table('teams')->where('id', $teamId)->first();

        if ($team->leader_id == $userId) {
            return response()->json(['message' => 'Leader tidak bisa dihapus'], 400);
        }

        DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'success' => true
        ]);
    }

    public function finalizeTeam($teamId)
    {
        $team = DB::table('teams')
            ->where('id', $teamId)
            ->where('leader_id', auth()->id())
            ->first();

        if (!$team) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ✅ assigned → accepted
        DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('status', 'assigned')
            ->update(['status' => 'accepted']);

        // ❌ lainnya → rejected
        DB::table('team_user')
            ->where('team_id', $teamId)
            ->whereIn('status', ['pending', 'invited'])
            ->update(['status' => 'rejected']);

        // 🔒 lock team
        DB::table('teams')
            ->where('id', $teamId)
            ->update(['status' => 'locked']);

        return response()->json([
            'success' => true
        ]);
    }

    public function finishCompetition(Request $request, $teamId)
    {
        $request->validate([
            'status_akhir' => 'required|in:winner,top_2,top_3,finalist,participant',
            'achievement_photo' => 'nullable|image|max:2048',
        ]);

        $team = Team::where('id', $teamId)->where('leader_id', auth()->id())->first();

        if (!$team)
            return response()->json(['message' => 'Unauthorized'], 403);

        $photoPath = $team->achievement_photo;
        if ($request->hasFile('achievement_photo')) {
            $photoPath = $request->file('achievement_photo')->store('achievements', 'public');
        }

        $team->update([
            'status' => 'completed',
            'rank' => $request->rank,
            'achievement_photo' => $photoPath,
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Pencapaian tim telah tercatat.']);
    }
}