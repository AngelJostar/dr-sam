<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'module',
        'status',
        'is_demo',
        'passwordless_review',
        'email_verified_at',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_demo' => 'boolean',
            'passwordless_review' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected function roleEnum(): Attribute
    {
        return Attribute::get(fn () => UserRole::tryFrom($this->role));
    }

    public function institution(): HasOne
    {
        return $this->hasOne(Institution::class, 'owner_user_id');
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class);
    }

    public function messengerProfile(): HasOne
    {
        return $this->hasOne(MessengerProfile::class);
    }
}

