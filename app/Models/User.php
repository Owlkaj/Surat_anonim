<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'display_label',
        'status',
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
        ];
    }

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class, 'sender_user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class, 'author_user_id');
    }

    public function adminUser(): HasOne
    {
        return $this->hasOne(AdminUser::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->adminUser !== null && $this->adminUser->role === 'admin' && $this->adminUser->disabled_at === null;
    }

    public function isModerator(): bool
    {
        return $this->adminUser !== null && in_array($this->adminUser->role, ['admin', 'moderator']) && $this->adminUser->disabled_at === null;
    }
}
