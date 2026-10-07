<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourceReservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'requester_email',
        'resource_id',
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'status',
        'approved_by',
        'approved_at',
        'google_event_id',
        'notes',
        'attachment_path',
        'number_of_pax',
        'setup_arrangement',
        'contact_number',
        'consumables',
        'floor_plan_path',
        'gate_pass_path',
        'approval_note',
        'recurrence_series_id',
        'recurrence_label',
        'recurrence_position',
        'recurrence_total',
        'billing_status',
        'soa_path',
        'soa_sent_at',
        'payment_due_at',
        'paid_at',
        'payment_proof_path',
        'soa_email_pending',
        'finished_confirmed_at',
        'finished_confirmed_by',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'approved_at' => 'datetime',
        'number_of_pax' => 'integer',
        'soa_sent_at' => 'date',
        'payment_due_at' => 'date',
        'paid_at' => 'datetime',
        'soa_email_pending' => 'boolean',
        'finished_confirmed_at' => 'datetime',
    ];

    public function billingEmails()
    {
        return $this->hasMany(FacilityBillingEmail::class, 'reservation_id');
    }

    public function latestBillingEmail()
    {
        return $this->hasOne(FacilityBillingEmail::class, 'reservation_id')->latestOfMany();
    }

    public function latestPaymentReminder()
    {
        return $this->hasOne(FacilityBillingEmail::class, 'reservation_id')->ofMany(['id' => 'max'], fn ($query) => $query->whereIn('kind', ['upcoming', 'due_today', 'overdue'])->where('status', 'sent'));
    }

    // 🔗 Owner
    public function scopeArchived($query)
    {
        return $query->where('status', 'approved')->where('end_datetime', '<=', now())
            ->where(fn ($q) => $q->where('billing_status', 'paid')->orWhere(fn ($free) => $free
                ->whereNotNull('finished_confirmed_at')->where('billing_status', 'unbilled')->whereNull('soa_path')));
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->status === 'approved' && $this->end_datetime?->lte(now())
            && ($this->billing_status === 'paid' || ($this->finished_confirmed_at !== null && $this->billing_status === 'unbilled' && !$this->soa_path));
    }

    public function getSoaLockedAttribute(): bool
    {
        return $this->billing_status === 'paid' || $this->paid_at !== null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 Primary resource (room)
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Resource::class, 'resource_reservation_rooms', 'reservation_id', 'resource_id')->orderBy('resources.name');
    }

    public function roomIds(): array
    {
        $ids = array_values(array_unique(array_merge($this->rooms->pluck('id')->all(), $this->resource_id ? [(int) $this->resource_id] : [])));
        sort($ids);
        return $ids;
    }

    public function getRoomNamesAttribute(): string
    {
        return $this->rooms->isNotEmpty() ? $this->rooms->pluck('name')->join(', ') : ($this->resource?->name ?? 'No room');
    }

    // 🔗 Equipment pivot
    public function items()
    {
        return $this->hasMany(ResourceReservationItem::class, 'reservation_id');
    }

    // 🔗 Shortcut to equipment models
    public function equipment()
    {
        return $this->belongsToMany(
            Resource::class,
            'resource_reservation_items',
            'reservation_id',
            'resource_id'
        )->withPivot('quantity');
    }

    // 🔗 Approver
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // 💡 Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
