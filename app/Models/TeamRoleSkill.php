<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamRoleSkill extends Model
{
    protected $fillable = [
        'team_role_id',
        'skill_name'
    ];

    public function role()
    {
        return $this->belongsTo(TeamRole::class, 'team_role_id');
    }
}
