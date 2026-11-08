<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourceReservation extends Model
{
    use HasFactory, SoftDeletes;
    protected $attributes = ['payment_required' => true];

    protected static function booted(): void
    {
        static::updated(function (self $reservation) {
            if ($reservation->wasChanged(['soa_path', 'requester_email'])) {
                $reservation->paymentSubmissions()->where('status', 'pending')->update([
                    'status' => 'superseded', 'review_note' => 'SOA or requester changed. Submit proof against the current SOA.',
                ]);
            }
        });
    }

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
        'payment_verifier_id',
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
        'payment_verifier_id' => 'integer',
    ];

    public function scopeSearch($query, ?string $search)
    {
        $term = mb_substr(trim((string) $search), 0, 200);
        if ($term === '') return $query;
        $pattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)) . '%';
        $dates = self::searchDateRange($term);
        return $query->where(function ($match) use ($term, $pattern, $dates) {
            foreach (['title', 'requester_name', 'requester_email'] as $column) {
                $match->orWhereRaw("LOWER({$column}) LIKE ? ESCAPE '!'", [$pattern]);
            }
            $roomMatch = fn ($rooms) => $rooms->whereRaw("LOWER(resources.name) LIKE ? ESCAPE '!'", [$pattern]);
            $match->orWhereHas('rooms', $roomMatch)->orWhereHas('resource', $roomMatch);
            $number = ltrim($term, '#');
            if (ctype_digit($number) && strlen($number) < 10) $match->orWhere('resource_reservations.id', (int) $number);
            if ($dates) {
                $match->orWhere(fn ($event) => $event
                    ->where('start_datetime', '<', $dates[1])
                    ->where('end_datetime', '>', $dates[0]));
            }
        });
    }

    private static function searchDateRange(string $term): ?array
    {
        // Strict formats avoid interpreting ordinary event names as relative dates.
        $term = preg_replace('/\s+/', ' ', str_replace(',', '', trim($term)));
        $formats = match (true) {
            (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $term) => ['Y-m-d'],
            (bool) preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $term) => ['m/d/Y'],
            (bool) preg_match('/^\d{4}-\d{2}$/', $term) => ['Y-m'],
            (bool) preg_match('/^[a-z]+ \d{4}$/i', $term) => ['M Y', 'F Y'],
            (bool) preg_match('/^[a-z]+ \d{1,2} \d{4}$/i', $term) => ['M j Y', 'F j Y'],
            (bool) preg_match('/^\d{1,2} [a-z]+ \d{4}$/i', $term) => ['j M Y', 'j F Y'],
            default => [],
        };
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $term, new \DateTimeZone(config('app.timezone')));
            $errors = \DateTimeImmutable::getLastErrors();
            if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))) continue;
            $end = $date->modify(in_array($format, ['Y-m', 'M Y', 'F Y']) ? '+1 month' : '+1 day');
            return [$date->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
        }
        return null;
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

    public function paymentSubmissions()
    {
        return $this->hasMany(FacilityPaymentSubmission::class, 'reservation_id');
    }

    public function paymentOverrides()
    {
        return $this->hasMany(FacilityPaymentOverride::class, 'reservation_id')->latest('id');
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
            ->whereDoesntHave('paymentSubmissions', fn ($q) => $q->where('status', 'pending'))
            ->where('billing_status', '!=', 'paid')->whereNull('paid_at')
            ->where(fn ($q) => $q->where(fn ($soa) => $soa->whereNotNull('soa_path')
                ->where(fn ($unsent) => $unsent->where('soa_email_pending', true)->orWhereNull('soa_sent_at')))
                ->orWhere(fn ($finished) => $finished->whereNotNull('finished_confirmed_at')->where('billing_status', 'unbilled')->whereNull('soa_sent_at')));
    }

    public function getIsAwaitingBillingAttribute(): bool
    {
        if (($this->pending_payment_count ?? $this->paymentSubmissions()->where('status', 'pending')->count()) > 0) return false;
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
