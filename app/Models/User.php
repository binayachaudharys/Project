<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'role' => UserRole::class,
        ];
    }

    public function appointmentsAsCustomer(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    public function appointmentsAsStaff(): HasMany
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }

    public function salesAsCustomer(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }

    public function salesAsStaff(): HasMany
    {
        return $this->hasMany(Sale::class, 'staff_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Post-login home: owner admin, staff today board, customer dashboard. */
    public function homeRoute(): string
    {
        return match ($this->role ?? UserRole::Customer) {
            UserRole::Owner => 'admin.dashboard',
            UserRole::Staff => 'staff.today',
            default => 'dashboard',
        };
    }
}
