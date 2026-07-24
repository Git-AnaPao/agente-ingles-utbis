<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasUuids, Notifiable, CanResetPassword;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_email',
        'google_id',
        'user_cel',
        'user_password',
        'user_name',
        'user_last_name',
        'user_middle_name',
        'user_status',
    ];

    protected $hidden = [
        'user_password',
        'remember_token',
        'roles',
    ];

    protected $appends = ['name', 'email', 'role'];

    public function getNameAttribute(): string
    {
        return trim("{$this->user_name} {$this->user_middle_name} {$this->user_last_name}");
    }

    public function getEmailAttribute(): string
    {
        return $this->user_email;
    }

    public function getRoleAttribute(): string
    {
        return $this->roles->first()?->role_name ?? 'student';
    }

    public function getAuthPassword()
    {
        return $this->user_password;
    }

    public function getEmailForPasswordReset()
    {
        return $this->user_email;
    }

    public function routeNotificationForMail($notification)
    {
        return $this->user_email;
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [
            'user_password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains(fn($r) => $r->role_name === $roleName);
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function isProfessor(): bool
    {
        return $this->hasRole('professor');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class, 'student_id');
    }

    public function attemptLogs(): HasMany
    {
        return $this->hasMany(AttemptLog::class, 'user_id');
    }

    public function placementTests(): HasMany
    {
        return $this->hasMany(PlacementTest::class, 'student_id');
    }

    public function username(): string
    {
        return 'user_email';
    }
}
