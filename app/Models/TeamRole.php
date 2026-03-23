<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamRole extends Model
{
    protected $fillable = [
        'team_id',
        'role_name',
        'max_slot'
    ];

    public function users()
    {
        // Relasi ke User melalui tabel pivot team_user
        return $this->belongsToMany(User::class, 'team_user', 'role_id', 'user_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function skills()
    {
        return $this->hasMany(TeamRoleSkill::class, 'team_role_id');
    }
}
