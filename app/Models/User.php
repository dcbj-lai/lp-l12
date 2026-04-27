<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasRoles;

    protected string $guard_name = 'web';

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
        'legacy_roles', // ✅ updated
        'payroll_on',
        'monthly_rate',
        'department_id',
        'package',
        'rank',
        'position',
        'check_in_mode',
        'birthdate',      // ✅ added
        'hire_date',      // ✅ added
        'preferred_name',
        'profile_photo_path',
        'phone_work',
        'phone_mobile',
        'address',        // ✅ added
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
        'legacy_roles' => '["user"]',
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
            'legacy_roles' => 'array', // ✅ updated
            'payroll_on' => 'boolean',
            'monthly_rate' => 'decimal:2',

            // ✅ IMPORTANT: date casting
            'birthdate' => 'date',
            'hire_date' => 'date',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)->trim()->substr(0, 1)->upper();
    }

    public function hasAnyLegacyRole(array $roles): bool
    {
        return count(array_intersect($this->legacy_roles ?? [], $roles)) > 0;
    }

    /**
     * Automatically corrects invalid values to No
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
        return in_array('finance.admin', $this->legacy_roles ?? []) || in_array('super.admin', $this->legacy_roles ?? []);
    }

    public function isPNCAdmin(): bool
    {
        return in_array('pnc.admin', $this->legacy_roles ?? []) || in_array('super.admin', $this->legacy_roles ?? []);
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
        return $this->rank === 'manager';
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function isGuidanceAdmin(): bool
    {
        return in_array('guidance.admin', $this->legacy_roles ?? []);
    }

    public function isGuidanceStaff(): bool
    {
        return in_array('guidance.staff', $this->legacy_roles ?? []);
    }
}
