<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class EventAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'file_path',
        'original_name',
        'disk',
        'uploaded_by',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Full URL for the stored file on its disk
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?? 'private_s3')->url($this->file_path);
    }
}
