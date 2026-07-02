<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_number',
        'name',
        'email',
        'is_active',
        'password',
        'google_id',
        'supervisor_id',
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
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'dietary_preference',
        'medical_notes',
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

    public function cardUrl(): string
    {
        return 'https://lp.life.edu.ph/card/' . Str::slug($this->preferred_name ?: $this->name);
    }

    public function vcardUrl(): string
    {
        return $this->cardUrl();
    }

    public function canApproveAnyLeaveRequest(): bool
    {
        return $this->hasAnyRole(['pnc.super', 'pnc.admin', 'super.admin']);
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
        return $this->hasAnyRole(['finance.admin', 'super.admin']);
    }

    public function isPNCAdmin(): bool
    {
        return $this->hasAnyRole(['pnc.admin', 'super.admin']);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function requestCredit()
    {
        return $this->hasOne(RequestCredit::class);
    }

    public function leaveReplenishmentRunItems()
    {
        return $this->hasMany(LeaveReplenishmentRunItem::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
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
        return $this->hasRole('guidance.admin');
    }

    public function isGuidanceStaff(): bool
    {
        return $this->hasRole('guidance.staff');
    }
}
