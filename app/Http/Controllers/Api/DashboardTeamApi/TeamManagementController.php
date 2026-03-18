<?php

namespace App\Http\Controllers\Api\DashboardTeamAPi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\UserJoinedTeam;
use App\Models\TeamRole;
use App\Models\Team;

class TeamManagementController extends Controller
{
    // LIST JOIN REQUESTS
    public function requests($teamId)
    {
        $requests = DB::table('team_user')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->where('team_user.team_id', $teamId)
            ->whereIn('team_user.status', ['pending', 'invited'])
            ->select('users.id', 'users.name', 'users.major', 'team_user.id as request_id', 'team_user.status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function invite(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $team = DB::table('teams')
            ->where('id', $request->team_id)
            ->where('leader_id', auth()->id())
            ->first();

        if (!$team) {
            return response()->json(['message' => 'Hanya leader yang bisa mengundang'], 403);
        }

        $exists = DB::table('team_user')
            ->where('team_id', $request->team_id)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'User sudah ada di tim atau sudah di-invite'], 400);
        }

        DB::table('team_user')->insert([
            'team_id' => $request->team_id,
            'user_id' => $request->user_id,
            'status' => 'invited',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        event(new UserJoinedTeam("Kamu diundang bergabung ke tim: " . $team->name, $request->user_id));

        return response()->json([
            'success' => true,
            'message' => 'Undangan berhasil dikirim secara real-time!'
        ]);
    }

    public function respondInvite(Request $request)
    {
        $request->validate([
            'invite_id' => 'required|exists:team_user,id',
            'action' => 'required|in:accept,reject' // Hanya boleh isi accept atau reject
        ]);

        $status = $request->action === 'accept' ? 'accepted' : 'rejected';

        // Cari baris team_user berdasarkan invite_id dan pastikan milik user yang sedang login
        $update = DB::table('team_user')
            ->where('id', $request->invite_id)
            ->where('user_id', auth()->id())
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);

        if (!$update) {
            return response()->json(['message' => 'Undangan tidak ditemukan atau akses ditolak'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Undangan berhasil " . ($request->action === 'accept' ? 'diterima' : 'ditolak')
        ]);
    }

    // APPROVE REQUEST + ASSIGN ROLE (Drag & Drop)
    public function assignRole(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:team_user,id',
            'role_id' => 'required|exists:team_roles,id'
        ]);

        try {
            DB::transaction(function () use ($request) {

                $join = DB::table('team_user')
                    ->join('teams', 'teams.id', '=', 'team_user.team_id')
                    ->where('team_user.id', $request->request_id)
                    ->where('teams.leader_id', auth()->id())
                    ->select('team_user.*')
                    ->first();

                if (!$join) {
                    throw new \Exception('Kamu tidak memiliki akses atau request tidak ditemukan');
                }

                // hitung slot terisi
                $usedSlot = DB::table('team_user')
                    ->where('team_id', $join->team_id)
                    ->where('role_id', $request->role_id)
                    ->where('status', 'accepted')
                    ->lockForUpdate()
                    ->count();

                $role = DB::table('team_roles')
                    ->where('id', $request->role_id)
                    ->lockForUpdate()
                    ->first();

                if ($usedSlot >= $role->max_slot) {
                    throw new \Exception('Slot role sudah penuh');
                }

                DB::table('team_user')
                    ->where('id', $request->request_id)
                    ->update([
                        'status' => 'accepted',
                        'role_id' => $request->role_id,
                        'updated_at' => now()
                    ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'User berhasil di-assign ke role'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function teamStructure($teamId)
    {
        // Pastikan hanya leader yang bisa melihat struktur manajemen ini
        $team = DB::table('teams')->where('id', $teamId)->where('leader_id', auth()->id())->first();

        if (!$team) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Ambil data Roles beserta Member yang sudah masuk (Accepted)
        $roles = TeamRole::where('team_id', $teamId)
            ->with([
                'users' => function ($q) use ($teamId) {
                    $q->where('team_user.team_id', $teamId)
                        ->where('team_user.status', 'accepted')
                        ->select('users.id', 'users.name', 'users.avatar');
                }
            ])
            ->with('skills') // Jika kamu pakai tabel skills untuk role tersebut
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'role_name' => $role->role_name,
                    'max_slot' => $role->max_slot,
                    'filled' => $role->users->count(),
                    'is_full' => $role->users->count() >= $role->max_slot,
                    'members' => $role->users,
                    'required_skills' => $role->skills->pluck('skill_name'),
                ];
            })
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
                // 1. Cek apakah yang akses adalah Leader
                $team = DB::table('teams')
                    ->where('id', $request->team_id)
                    ->where('leader_id', auth()->id())
                    ->first();

                if (!$team)
                    throw new \Exception('Unauthorized');

                // 2. Cek slot di role tujuan
                $usedSlot = DB::table('team_user')
                    ->where('team_id', $request->team_id)
                    ->where('role_id', $request->new_role_id)
                    ->where('status', 'accepted')
                    ->count();

                $role = DB::table('team_roles')->where('id', $request->new_role_id)->first();

                if ($usedSlot >= $role->max_slot) {
                    throw new \Exception('Role tujuan sudah penuh');
                }

                // 3. Update role_id user tersebut
                DB::table('team_user')
                    ->where('team_id', $request->team_id)
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

    // REJECT REQUEST
    public function reject($requestId)
    {
        DB::table('team_user')
            ->where('id', $requestId)
            ->update([
                'status' => 'rejected',
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Request ditolak'
        ]);
    }

    // REMOVE MEMBER
    public function removeMember($teamId, $userId)
    {
        if ($userId == auth()->id()) {
            return response()->json(['message' => 'Leader tidak bisa keluar dengan cara ini'], 400);
        }

        DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil dihapus'
        ]);
    }

    public function finishCompetition(Request $request, $teamId)
    {
        $request->validate([
            'status_akhir' => 'required|in:winner,top_2,top_3,finalist,participant',
            'evidence_link' => 'nullable|url',
            'achievement_photo' => 'nullable|image|max:2048',
            'reflection' => 'nullable|string'
        ]);

        $team = Team::where('id', $teamId)->where('leader_id', auth()->id())->first();

        if (!$team) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $photoPath = $team->achievement_photo;
        if ($request->hasFile('achievement_photo')) {
            $photoPath = $request->file('achievement_photo')->store('achievements', 'public');
        }

        $team->update([
            'status' => 'completed',
            'rank' => $request->rank,
            'achievement_photo' => $photoPath,
            'description' => $request->testimonial ?? $team->description,
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Selamat! Pencapaian tim telah tercatat.',
            'data' => $team
        ]);
    }
}
