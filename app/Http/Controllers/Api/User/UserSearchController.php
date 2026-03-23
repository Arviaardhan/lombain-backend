<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserSearchController extends Controller
{
    public function search(Request $request)
    {
        // 1. Inisialisasi Query dengan Hitung Relasi untuk Card
        $query = User::where('id', '!=', auth()->id())
            ->withCount([
                'joinedTeams as teams_count' => function ($q) {
                    $q->where('team_user.status', 'accepted');
                },
                'joinedTeams as projects_count' => function ($q) {
                    $q->where('teams.status', 'completed');
                }
            ]);

        // 2. Filter Berdasarkan Pencarian Teks (Nama, Jurusan, Institusi)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('institution', 'like', "%{$search}%")
                  ->orWhere('major', 'like', "%{$search}%")
                  ->orWhere('skills', 'like', "%{$search}%");
            });
        }

        // 3. Filter Eksplisit: Jurusan (Array)
        if ($request->filled('majors')) {
            $majors = explode(',', $request->majors);
            $query->whereIn('major', $majors);
        }

        // 4. Filter Eksplisit: Institusi (Array)
        if ($request->filled('institutions')) {
            $institutions = explode(',', $request->institutions);
            $query->whereIn('institution', $institutions);
        }

        // 5. Filter Eksplisit: Keahlian (JSON Search)
        if ($request->filled('skills')) {
            $skills = explode(',', $request->skills);
            $query->where(function ($q) use ($skills) {
                foreach ($skills as $skill) {
                    // Mencari di dalam kolom JSON skills
                    $q->orWhere('skills', 'like', "%{$skill}%");
                }
            });
        }

        // 6. Ambil Data dengan Pagination agar tidak berat
        $users = $query->latest()->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}