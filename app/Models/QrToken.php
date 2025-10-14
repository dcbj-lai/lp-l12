<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class QrToken extends Model
{
    protected $fillable = ['token', 'expires_at', 'active', 'type'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        return $this->active && $this->expires_at->isFuture();
    }

    public static function generate(string $type = 'check_in'): self
{
    // Step 1: Delete expired or inactive tokens older than 1 day
    static::where('expires_at', '<', now())->delete();

    // Step 2: Deactivate previous active tokens of the same type
    static::where('active', true)
        ->where('type', $type)
        ->update(['active' => false]);

    // Step 3: Create new secure token
    return static::create([
        'token' => bin2hex(random_bytes(16)),
        'type' => $type,
        'expires_at' => now()->addSeconds(60),
        'active' => true,
    ]);
}




}
