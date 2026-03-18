<?php

namespace App\Http\Controllers\Api\ExploreApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;

class ExploreController extends Controller
{
    public function index()
    {
        $teams = Team::with('leader')
            ->withCount('users')
            ->latest()
            ->paginate(10);

        $formatted = $teams->getCollection()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'competition_name' => $team->competition_name,
                'category' => $team->category,
                'max_members' => $team->max_members,
                'total_members' => $team->users_count,
                'slots_left' => $team->max_members - $team->users_count,
                'is_full' => $team->users_count >= $team->max_members,
                'leader' => [
                    'id' => $team->leader->id,
                    'name' => $team->leader->name,
                    'institution' => $team->leader->institution,
                    'major' => $team->leader->major,
                ]
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
