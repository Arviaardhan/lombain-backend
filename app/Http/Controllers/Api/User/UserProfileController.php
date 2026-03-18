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
            ->where('id', '!=', auth()->id()); 

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
        $userId = $id ?: auth()->id();

        $user = User::with([
            'teams' => function ($q) {
                $q->where('teams.status', 'completed')
                    ->select('teams.id', 'teams.name', 'teams.competition_name', 'teams.rank', 'teams.achievement_photo');
            },
            'joinedTeams' => function ($q) {
                $q->where('teams.status', 'active');
            }
        ])->findOrFail($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'bio' => $user->bio,
                'major' => $user->major,
                'institution' => $user->institution,
                'skills' => $user->skills, 
                'achievements' => $user->teams, 
                'current_projects' => $user->joinedTeams,
                'is_own_profile' => $userId == auth()->id()
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:500',
            'major' => 'nullable|string',
            'skills' => 'nullable|array',
        ]);

        $data = $request->only(['name', 'bio', 'major', 'skills']);

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => $user
        ]);
    }
}
