<?php

namespace App\Http\Controllers\Api\ExploreApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExploreController extends Controller
{
    public function index()
    {
        // Eager load leader dan roles agar data lengkap
        $teams = Team::with(['leader', 'roles.skills'])
            ->withCount('users')
            ->latest()
            ->paginate(10);

        $formatted = $teams->getCollection()->map(function ($team) {
            $fullDesc = $team->description ?? "Ayo bergabung dengan tim kami!";

            // Hitung sisa hari
            $daysLeft = $team->deadline ? now()->diffInDays(Carbon::parse($team->deadline), false) : null;

            // Mapping Roles & Skills
            $lookingFor = $team->roles->pluck('role_name')->toArray();
            $skills = $team->roles->flatMap(function ($role) {
                return $role->skills->pluck('skill_name');
            })->unique()->values()->toArray();

            $members = $team->users()
                ->wherePivot('status', 'accepted')
                ->get()
                ->map(function ($user) {
                    return [
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                    ];
                });

            return [
                'id' => $team->id,
                'title' => $team->name,
                'competition_name' => $team->competition_name,
                'campus' => $team->leader->institution ?? 'Umum',
                'category' => $team->category,
                'headline' => $team->headline,
                'description' => $fullDesc,
                'lookingFor' => $lookingFor,
                'skills' => $skills,
                'total_members' => $team->users_count,
                'max_members' => $team->max_members,
                'posted' => $team->created_at->diffForHumans(),
                'deadline' => $team->deadline ? $team->deadline->format('Y-m-d') : null,
                'leader' => [
                    'first_name' => $team->leader->first_name ?? null,
                    'last_name' => $team->leader->last_name ?? null,
                ],
                'members' => $members,
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