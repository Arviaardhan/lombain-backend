<?php

namespace App\Http\Controllers\Api\ExploreApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;

class ExploreController extends Controller
{
    public function index()
    {
        $teams = Team::with(['leader', 'roles.skills'])
            ->withCount('users')
            ->latest()
            ->paginate(10);

        $formatted = $teams->getCollection()->map(function ($team) {
            // 1. Ambil deskripsi asli dari database
            $fullDesc = $team->description ?? "Ayo bergabung dengan tim kami!";

            // 2. Buat deskripsi singkat (Limit 120 karakter) untuk Cards
            // Kita gunakan helper Str::limit agar rapi
            $shortDesc = \Illuminate\Support\Str::limit(strip_tags($fullDesc), 120, '...');

            $daysLeft = $team->deadline ? now()->diffInDays(\Carbon\Carbon::parse($team->deadline), false) : null;

            // 3. Mapping Roles & Skills (Seperti sebelumnya)
            $lookingFor = $team->roles->pluck('role_name')->toArray();
            $skills = $team->roles->flatMap(function ($role) {
                return $role->skills->pluck('skill_name');
            })->unique()->values()->toArray();

            return [
                'id' => $team->id,
                'title' => $team->name,
                'competition_name' => $team->competition_name,
                'campus' => $team->leader->institution ?? 'Umum',
                'category' => $team->category,
                'headline' => $team->headline, // Pastikan ini dikirim
                'description' => $fullDesc,
                'lookingFor' => $lookingFor, // Array of strings
                'skills' => $skills, // Array of strings
                'total_members' => $team->users_count, // Kita beri nama yang jelas
                'max_members' => $team->max_members,
                'posted' => $team->created_at->diffForHumans(),
                'daysLeft' => $daysLeft,
                'is_closing_soon' => ($daysLeft !== null && $daysLeft <= 3 && $daysLeft >= 0)
            ];
        });

        return response()->json([
            'success' => true,
            'teams' => $formatted,
            'meta' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'total' => $teams->total(),
            ]
        ]);
    }
}