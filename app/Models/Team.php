<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'name',
        'headline',
        'competition_name',
        'description',
        'category',
        'max_members',
        'leader_id',
        'guidebook_url',
        'deadline',
        'leader_role_name',
        'status'
    ];

    protected $casts = [
        'objectives' => 'array',
        'deadline' => 'date:Y-m-d',
    ];

    protected $with = ['leader'];

    public function roles()
    {
        return $this->hasMany(TeamRole::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'team_user',     // nama tabel pivot
            'team_id',       // foreign key ke team
            'user_id'        // foreign key ke user
        )
            ->withPivot(['status', 'role_id', 'role_name', 'note'])
            ->withTimestamps();
    }
}
