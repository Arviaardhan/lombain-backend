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
            'skills' => 'nullable|array' // Tambahkan nullable
        ]);

        // Gunakan try catch agar kita tahu errornya apa
        try {
            $newRole = DB::transaction(function () use ($request, $teamId) {
                $role = TeamRole::create([
                    'team_id' => $teamId,
                    'role_name' => $request->role_name,
                    'max_slot' => $request->max_slot
                ]);

                if ($request->has('skills')) {
                    foreach ($request->skills as $skill) {
                        $role->skills()->create([
                            'skill_name' => $skill
                        ]);
                    }
                }
                return $role;
            });

            return response()->json([
                'success' => true,
                'message' => 'Role berhasil dibuat',
                'data' => $newRole // Kembalikan data role baru untuk Next.js
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() // Ini akan kasih tahu error aslinya di Postman
            ], 500);
        }
    }

    // UPDATE ROLE
    public function updateRole(Request $request, $roleId)
    {
        $request->validate([
            'role_name' => 'required|string',
            'max_slot' => 'required|integer|min:1',
            'skills' => 'nullable|array'
        ]);

        try {
            $role = TeamRole::findOrFail($roleId);

            DB::transaction(function () use ($request, $role) {
                // 1. Update data Role utama
                $role->update([
                    'role_name' => $request->role_name,
                    'max_slot' => $request->max_slot
                ]);

                // 2. Sinkronisasi Skill (Hapus lama, buat baru)
                if ($request->has('skills') && is_array($request->skills)) {
                    $role->skills()->delete();

                    // Gunakan map atau collect jika ingin lebih "Laravel Style"
                    $newSkills = collect($request->skills)->map(function ($name) {
                        return ['skill_name' => $name];
                    })->toArray();

                    $role->skills()->createMany($newSkills);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Role and Skills updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
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
