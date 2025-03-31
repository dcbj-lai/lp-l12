<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ripple extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'content', 'file_path', 'pinned', 'parent_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(Ripple::class, 'parent_id');
    }

    public function likes()
    {
        return $this->hasMany(RippleLike::class);
    }

    public function isLikedByUser($userId)
{
    return $this->likes()->where('user_id', $userId)->exists();
}

    public function isPinned(): bool
{
    return $this->pinned === true;
}

public function hasFile(): bool
{
    return $this->file_path !== null;
}

}
