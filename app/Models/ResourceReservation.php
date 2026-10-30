<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourceReservation extends Model
{
    use HasFactory, SoftDeletes;
    protected $attributes = ['payment_required' => true];

    protected $fillable = [
        'user_id',
        'requester_email',
        'requester_name',
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
        'payment_required',
        'completion_notes',
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
        'payment_required' => 'boolean',
    ];

    public function scopeSearch($query, ?string $search)
    {
        $term = mb_substr(trim((string) $search), 0, 200);
        if ($term === '') return $query;
        $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)) . '%';
        return $query->where(function ($match) use ($term, $pattern) {
            foreach (['title', 'requester_name', 'requester_email'] as $column) {
                $match->orWhereRaw("LOWER({$column}) LIKE ? ESCAPE '!'", [$pattern]);
            }
            $roomMatch = fn ($rooms) => $rooms->whereRaw("LOWER(resources.name) LIKE ? ESCAPE '!'", [$pattern]);
            $match->orWhereHas('rooms', $roomMatch)->orWhereHas('resource', $roomMatch);
            $number = ltrim($term, '#');
            if (ctype_digit($number) && strlen($number) < 10) $match->orWhere('resource_reservations.id', (int) $number);
        });
    }

    public function billingEmails()
    {
        return $this->hasMany(FacilityBillingEmail::class, 'reservation_id');
    }

    public function latestCancellationRequest()
    {
        return $this->hasOne(FacilityReservationCancellation::class, 'reservation_id')->latestOfMany();
    }

    public function paymentProofs()
    {
        return $this->hasMany(FacilityPaymentProof::class, 'reservation_id')->orderBy('id');
    }

    public function soaRevisions()
    {
        return $this->hasMany(FacilitySoaRevision::class, 'reservation_id')->latest('id');
    }

    public function latestGeneratedSoa()
    {
        return $this->hasOne(FacilityGeneratedSoa::class, 'reservation_id')->latestOfMany();
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
        return $query->where('status', 'approved')->where('end_datetime', '<=', now())->where('soa_email_pending', false)
            ->whereDoesntHave('latestCancellationRequest', fn ($q) => $q->where('status', 'review'))
            ->where(fn ($q) => $q->where('billing_status', 'paid')->orWhere(fn ($free) => $free
                ->whereNotNull('finished_confirmed_at')->where('payment_required', false)->where('billing_status', 'unbilled')->whereNull('soa_path')));
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->status === 'approved' && $this->end_datetime?->lte(now()) && !$this->soa_email_pending
            && $this->latestCancellationRequest?->status !== 'review'
            && ($this->billing_status === 'paid' || ($this->finished_confirmed_at !== null && !$this->payment_required && $this->billing_status === 'unbilled' && !$this->soa_path));
    }

    public function scopeAwaitingBilling($query)
    {
        return $query->where('status', 'approved')->where('payment_required', true)
            ->where('billing_status', '!=', 'paid')->whereNull('paid_at')
            ->where(fn ($q) => $q->where(fn ($soa) => $soa->whereNotNull('soa_path')
                ->where(fn ($unsent) => $unsent->where('soa_email_pending', true)->orWhereNull('soa_sent_at')))
                ->orWhere(fn ($finished) => $finished->whereNotNull('finished_confirmed_at')->where('billing_status', 'unbilled')->whereNull('soa_sent_at')));
    }

    public function getIsAwaitingBillingAttribute(): bool
    {
        return $this->status === 'approved' && $this->payment_required && !$this->soa_locked
            && (($this->soa_path && ($this->soa_email_pending || !$this->soa_sent_at))
                || ($this->finished_confirmed_at && $this->billing_status === 'unbilled' && !$this->soa_sent_at));
    }

    public function getBillingViewAttribute(): string
    {
        if ($this->is_archived) return 'archive';
        return 'approved';
    }

    public function getBillingFilterAttribute(): string
    {
        if ($this->soa_locked) return 'paid';
        if ($this->is_awaiting_billing) return 'unbilled';
        return $this->billing_status === 'billed' ? 'billed' : 'unbilled';
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
