<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'name',
        'competition_name',
        'description',
        'category',
        'max_members',
        'leader_id',
        'guidebook_url',
        'deadline'
    ];

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
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }
}
