<?php

namespace App\Http\Controllers\Api\DashboardTeamAPi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TeamRole;

class TeamRoleController extends Controller
{
    // CREATE ROLE
    public function storeRole(Request $request, $teamId)
    {
        $request->validate([
            'role_name' => 'required|string',
            'max_slot' => 'required|integer|min:1',
            'skills' => 'array'
        ]);

        DB::transaction(function () use ($request, $teamId) {

            $role = TeamRole::create([
                'team_id' => $teamId,
                'role_name' => $request->role_name,
                'max_slot' => $request->max_slot
            ]);

            if ($request->skills) {
                foreach ($request->skills as $skill) {
                    $role->skills()->create([
                        'skill_name' => $skill
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dibuat'
        ]);
    }

    // UPDATE ROLE
    public function updateRole(Request $request, $roleId)
    {
        $role = TeamRole::findOrFail($roleId);

        $role->update([
            'role_name' => $request->role_name ?? $role->role_name,
            'max_slot' => $request->max_slot ?? $role->max_slot
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diupdate'
        ]);
    }

    // DELETE ROLE
    public function deleteRole($roleId)
    {
        $role = TeamRole::findOrFail($roleId);
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dihapus'
        ]);
    }
}
