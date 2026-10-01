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
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'approved_at' => 'datetime',
        'number_of_pax' => 'integer',
        'soa_sent_at' => 'date',
        'payment_due_at' => 'date',
        'paid_at' => 'datetime',
    ];

    // 🔗 Owner
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 Primary resource (room)
    public function resource()
    {
        return $this->belongsTo(Resource::class);
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
