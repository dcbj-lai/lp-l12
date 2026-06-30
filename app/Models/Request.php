<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Request extends Model
{
    use HasFactory;

    public const TYPE_PTO = 'PTO';
    public const TYPE_WFH = 'WFH';
    public const TYPE_LWOP = 'LWOP';
    public const TYPE_CREDIT_CARRY_OVER = 'CREDIT_CARRY_OVER';

    protected $fillable = [
        'user_id',
        'approver_id',
        'type',
        'is_offset',
        'offset_proof_path',
        'reason',
        'start_date',
        'end_date',
        'end_date_type',
        'number_of_days',
        'status',
        'remarks',
    ];


    /* ----------------------
     |  Offset proof helpers
     |-----------------------*/

    public function offsetProofFolder(): string
    {
        $username = $this->user->name ?? 'unknown';
        $username = preg_replace('/[^A-Za-z0-9_-]/', '', str_replace(' ', '_', $username));

        return "requests/{$this->user_id}-{$username}";
    }

    public static function sanitizeFilename(string $name): string
    {
        return preg_replace(
            '/[^A-Za-z0-9._-]/',
            '',
            str_replace(' ', '_', basename($name))
        );
    }

    public function deleteOffsetProof(): void
    {
        if (
            $this->offset_proof_path &&
            Storage::disk('private_s3')->exists($this->offset_proof_path)
        ) {
            Storage::disk('private_s3')->delete($this->offset_proof_path);
        }
    }

    public function isCreditCarryOver(): bool
    {
        return $this->type === self::TYPE_CREDIT_CARRY_OVER;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PTO => 'Leave',
            self::TYPE_WFH => 'Work from Home',
            self::TYPE_LWOP => 'Leave w/o Pay',
            self::TYPE_CREDIT_CARRY_OVER => 'Credit Carry Over',
            default => ucfirst(strtolower(str_replace('_', ' ', $this->type))),
        };
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
