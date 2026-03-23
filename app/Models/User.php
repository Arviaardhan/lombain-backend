<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'institution',
        'major',
        'skill_category_id',
        'skills',
        'bio',
        'github_url',
        'linkedin_url',
        'portfolio_url'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'skills' => 'array',
        ];
    }

    protected $casts = [
        'skills' => 'array',
    ];

    public function skillCategory()
    {
        return $this->belongsTo(SkillCategory::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('status', 'role', 'role_id', 'role_name')
            ->withTimestamps();
    }

    public function joinedTeams()
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('status', 'role', 'role_id')
            ->withTimestamps();
    }

    public function getAvatarAttribute($value)
    {
        if (!$value)
            return null;
        return asset('storage/' . $value); // Sesuaikan dengan folder simpanmu
    }

    public function ledTeams()
    {
        return $this->hasMany(Team::class, 'leader_id');
    }
}
