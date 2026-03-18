<?php

namespace App\Http\Controllers\Api\DashboardTeamAPi;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'competition_name' => 'required|string',
            'category' => 'required|string',
            'max_members' => 'required|integer|min:1',
            'deadline' => 'required|date',
            'roles' => 'required|array'
        ]);

        $user = $request->user();

        DB::transaction(function () use ($request, $user, &$team) {

            $team = Team::create([
                'name' => $request->name,
                'competition_name' => $request->competition_name,
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
                'status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // create roles + skills
            foreach ($request->roles as $roleData) {

                $role = $team->roles()->create([
                    'role_name' => $roleData['role_name'] ?? 'General Member', 
                    'max_slot'  => $roleData['max_slot'] ?? 1,
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
        $team = Team::with([
            'leader',
            'roles.skills',
            'users' => function ($q) {
                $q->wherePivot('status', 'accepted')
                    ->withPivot('role_id', 'status');
            }
        ])->findOrFail($id);

        // add slot info
        $roles = $team->roles->map(function ($role) use ($team) {
            $filled = DB::table('team_user')
                ->where('team_id', $team->id)
                ->where('role_id', $role->id)
                ->where('status', 'accepted')
                ->count();

            return [
                'id' => $role->id,
                'role_name' => $role->role_name,
                'max_slot' => $role->max_slot,
                'filled' => $filled,
                'skills' => $role->skills
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                ...$team->toArray(),
                'roles' => $roles
            ]
        ]);
    }

    // JOIN TEAM + CREATE JOIN REQUEST
    public function join(Request $request, $teamId)
    {
        $user = $request->user();

        $exists = DB::table('team_user')
            ->where('team_id', $teamId)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu sudah request/join team ini'
            ], 400);
        }

        DB::table('team_user')->insert([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request join berhasil'
        ]);
    }
}