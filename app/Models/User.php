<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'supervisor_id',
        'roles',
        'payroll_on',
        'monthly_rate',
        'department_id',
        'package',
        'rank',
        'position',
        'check_in_mode',
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

    protected $attributes = [
        'roles' => '["user"]', // Ensure default role is "User"
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
            'roles' => 'array',
            'payroll_on' => 'boolean',
            'monthly_rate' => 'decimal:2', // Ensures proper decimal formatting
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }
    public function hasAnyRole(array $roles): bool
    {
        return count(array_intersect($this->roles, $roles)) > 0;
    }
    /**
     * Automatically corrects invalid values to No
     * @param mixed $value
     * @return void
     */
    public function setPayrollOnAttribute($value)
{
    $this->attributes['payroll_on'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

public function adjustments()
{
    return $this->hasMany(Adjustment::class)->latest();
}


public function isFinanceAdmin(): bool
{
    return in_array('finance.admin', $this->roles ?? []) || in_array('super.admin', $this->roles ?? []);
}

public function isPNCAdmin(): bool
{
    return in_array('pnc.admin', $this->roles ?? []) || in_array('super.admin', $this->roles ?? []);
}

public function supervisor()
{
    return $this->belongsTo(User::class, 'supervisor_id');
}

public function requestCredit()
{
    return $this->hasOne(RequestCredit::class);
}

public function isManager()
{
    return $this->rank==='manager';
}

public function department()
{
    return $this->belongsTo(Department::class);
}

public function isGuidance(): bool
{
    return in_array('guidance.admin', $this->roles ?? []);
}

}
