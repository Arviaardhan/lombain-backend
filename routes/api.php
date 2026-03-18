<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DashboardTeamApi\TeamController;
use App\Http\Controllers\Api\DashboardTeamApi\TeamManagementController;
use App\Http\Controllers\Api\DashboardTeamApi\TeamRoleController;
use App\Http\Controllers\Api\ExploreApi\ExploreController;
use App\Http\Controllers\Api\User\UserTeamController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SkillCategoryController;
use App\Http\Controllers\Api\User\UserDashboardController;
use App\Http\Controllers\Api\User\UserSearchController;
use App\Http\Controllers\Api\User\UserProfileController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Skill
Route::get('/skill-categories', [SkillCategoryController::class, 'index']);


// Explore (PUBLIC)
Route::get('/explore/teams', [ExploreController::class, 'index']);


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // Team
    Route::post('/create-teams', [TeamController::class, 'store']);
    Route::get('/teams/{id}', [TeamController::class, 'show']);
    Route::post('/teams/{id}/join', [TeamController::class, 'join']);

    // My Teams
    Route::get('/my/teams', [UserTeamController::class, 'myTeams']);

    // Management
    Route::get('/teams/{id}/requests', [TeamManagementController::class, 'requests']);
    Route::post('/teams/invite', [TeamManagementController::class, 'invite']);
    Route::post('/teams/respond-invite', [TeamManagementController::class, 'respondInvite']);
    Route::post('/teams/assign-role', [TeamManagementController::class, 'assignRole']);
    Route::post('/teams/reject/{id}', [TeamManagementController::class, 'reject']);
    Route::delete('/teams/{teamId}/members/{userId}', [TeamManagementController::class, 'removeMember']);

    // Roles
    Route::post('/teams/{id}/roles', [TeamRoleController::class, 'store']);
    Route::put('/update-roles/{id}', [TeamRoleController::class, 'update']);
    Route::delete('/delete-roles/{id}', [TeamRoleController::class, 'destroy']);

    // Dashboard
    Route::get('/user/dashboard', [UserDashboardController::class, 'index']);
    Route::get('/users/search', [UserSearchController::class, 'search']);
    Route::get('/teams/{id}/structure', [TeamManagementController::class, 'teamStructure']);

    // Profil sendiri
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::post('/profile/update', [UserProfileController::class, 'update']);
    
    // Profil orang lain (Talents)
    Route::get('/talents/{id}', [UserProfileController::class, 'show']);
    Route::get('/talents', [UserProfileController::class, 'index'])->middleware('auth:sanctum');
});