<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserProfileController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['skillCategory'])
            ->where('id', '!=', auth()->id())
            // TAMBAHKAN INI: Hitung relasi secara otomatis
            ->withCount([
                // Hitung semua tim yang diikuti (accepted)
                'joinedTeams as teams_count' => function ($q) {
                    $q->where('team_user.status', 'accepted');
                },
                // Hitung proyek (misal: tim yang statusnya sudah 'completed')
                'joinedTeams as projects_count' => function ($q) {
                    $q->where('teams.status', 'completed');
                }
            ]);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%")
                    ->orWhere('major', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('skill_category_id', $request->category_id);
        }

        $users = $query->latest()->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function show($id = null)
    {
        try {
            $userId = $id ?: auth()->id();

            // Gunakan eager loading agar tidak lambat (N+1 query)
            $user = User::with([
                'skillCategory',
                'teams' => function ($q) {
                    $q->where('teams.status', 'completed');
                },
                'joinedTeams' => function ($q) {
                    $q->where('teams.status', 'active');
                }
            ])->findOrFail($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'bio' => $user->bio,
                    'major' => $user->major,
                    'institution' => $user->institution,
                    'skills' => $user->skills,
                    'category' => $user->skillCategory?->name,
                    'achievements' => $user->teams,
                    'github_url' => $user->github_url,
                    'linkedin_url' => $user->linkedin_url,
                    'portfolio_url' => $user->portfolio_url,
                    'current_projects' => $user->joinedTeams,
                    'is_own_profile' => $userId == auth()->id()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:500',
            'major' => 'nullable|string',
            'skills' => 'nullable|array',
            'institution' => 'nullable|string',
            'github_url' => 'nullable|string',
            'linkedin_url' => 'nullable|string',
            'portfolio_url' => 'nullable|string',
        ]);

        // Masukkan kolom URL ke dalam array yang boleh di-update
        $data = $request->only([
            'name',
            'bio',
            'major',
            'skills',
            'institution',
            'github_url',
            'linkedin_url',
            'portfolio_url'
        ]);

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => $user
        ]);
    }
}
