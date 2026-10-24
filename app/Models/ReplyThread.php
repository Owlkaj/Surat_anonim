<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReplyThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_id',
        'recipient_user_id',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class, 'letter_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class, 'thread_id')->orderBy('created_at', 'asc');
    }

    /**
     * Balasan yang boleh ditampilkan ke kedua pihak.
     * Balasan yang sedang ditinjau atau ditakedown tetap tersedia untuk moderator.
     */
    public function visibleReplies(): HasMany
    {
        return $this->replies()->where('moderation_status', 'approved');
    }
}
