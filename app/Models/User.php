<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Same defaults as the database columns, so new instances agree with stored rows. */
    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
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
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /** Active admins other than this account. Used for the "at least one active admin" rule. */
    public function otherActiveAdminCount(): int
    {
        return static::where('role', 'admin')->where('is_active', true)->whereKeyNot($this->id)->count();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** True when this account created the record (ownership rules in PRD.md §4). */
    public function owns(?int $ownerId): bool
    {
        return $ownerId !== null && $ownerId === $this->id;
    }
}
