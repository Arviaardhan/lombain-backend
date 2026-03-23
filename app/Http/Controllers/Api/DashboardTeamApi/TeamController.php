<?php

namespace App\Http\Controllers\Api\DashboardTeamAPi;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::with('leader')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $teams
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'headline' => 'required|string|max:150',
            'competition_name' => 'required|string',
            'category' => 'required|string',
            'max_members' => 'required|integer|min:1',
            'deadline' => 'required|date',
            'roles' => 'required|array',
            'leader_role_name' => 'required|string|max:100'
        ]);

        $user = $request->user();

        DB::transaction(function () use ($request, $user, &$team) {

            $team = Team::create([
                'name' => $request->name,
                'headline' => $request->headline,
                'competition_name' => $request->competition_name,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'category' => $request->category,
                'max_members' => $request->max_members,
                'leader_id' => $user->id,
                'guidebook_url' => $request->guidebook_url,
                'deadline' => $request->deadline
            ]);

            // leader masuk team
            DB::table('team_user')->insert([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'leader',
                'role_name' => $request->leader_role_name,
                'status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // create roles + skills
            foreach ($request->roles as $roleData) {

                $role = $team->roles()->create([
                    'role_name' => $roleData['role_name'] ?? 'General Member',
                    'max_slot' => $roleData['max_slot'] ?? 1,
                ]);

                if (isset($roleData['skills'])) {
                    foreach ($roleData['skills'] as $skill) {
                        $role->skills()->create([
                            'skill_name' => $skill
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Team berhasil dibuat',
            'data' => $team->load('roles.skills')
        ]);
    }

    // TEAM DETAIL
    public function show($id)
    {
        $user = auth()->user();
        $team = Team::with(['leader', 'roles.skills', 'users'])->find($id);

        // Cek status join user saat ini
        $userStatus = DB::table('team_user')
            ->where('team_id', $id)
            ->where('user_id', $user->id)
            ->value('status'); // Mengambil string status: 'pending', 'accepted', dll.

        return response()->json([
            'success' => true,
            'data' => $team,
            'my_status' => $userStatus ?? 'idle' // Kirim status ke frontend
        ]);
    }

    public function edit($id)
    {
        $team = Team::with(['roles.skills'])->findOrFail($id);

        // Pastikan hanya leader yang bisa edit
        if ($team->leader_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $team
        ]);
    }

    // Proses Update Data
    public function update(Request $request, $id)
    {
        $team = Team::findOrFail($id);

        if ($team->leader_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|nullable|string',
            'headline' => 'sometimes|nullable|string|max:150',
            'competition_name' => 'sometimes|nullable|string',
            'description' => 'nullable|string',
            'category' => 'sometimes|nullable|string',
            'deadline' => 'sometimes|nullable|date',
        ]);

        $team->update($request->only([
            'name',
            'headline',
            'competition_name',
            'description',
            'category',
            'max_members',
            'deadline',
            'guidebook_url'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Team updated successfully!',
            'data' => $team
        ]);
    }

    // JOIN TEAM + CREATE JOIN REQUEST
    public function join(Request $request, $id)
    {
        // 1. Matikan validasi Rule::exists sementara
        $request->validate([
            'role_id' => 'required', // Hanya pastikan role_id dikirim
            'note' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $id = (int) $id;

        // 2. Debugging: Kita cek manual di sini sebelum insert
        $roleExists = DB::table('team_roles')
            ->where('id', $request->role_id)
            ->where('team_id', $id)
            ->exists();

        if (!$roleExists) {
            return response()->json([
                'success' => false,
                'message' => "Data Role ID {$request->role_id} untuk Team ID {$id} memang tidak ada di DB!",
                'debug_info' => [
                    'input_role_id' => $request->role_id,
                    'input_team_id' => $id
                ]
            ], 422);
        }

        // 3. Jika lolos cek manual, masukkan data
        DB::table('team_user')->updateOrInsert(
            ['team_id' => $id, 'user_id' => $user->id],
            [
                'role_id' => $request->role_id,
                'note' => $request->note,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Akhirnya Ahmad Berhasil Join!']);
    }
}