<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_user_id',
        'target_contact_hmac',
        'target_email_encrypted',
        'target_label',
        'body',
        'music_url',
        'visibility',
        'moderation_status',
        'token_hash',
        'view_count',
        'published_at',
        'expires_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function thread(): HasOne
    {
        return $this->hasOne(ReplyThread::class, 'letter_id');
    }

    public function deliveryJobs(): HasMany
    {
        return $this->hasMany(DeliveryJob::class, 'letter_id');
    }

    public function latestDeliveryJob(): HasOne
    {
        return $this->hasOne(DeliveryJob::class, 'letter_id')->latestOfMany();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'content_id')->where('content_type', 'letter');
    }

    public function isAccessible(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if (in_array($this->moderation_status, ['rejected', 'takedown', 'under_review'])) {
            return false;
        }

        return true;
    }

    public function isPublicActive(): bool
    {
        return $this->visibility === 'public'
            && $this->moderation_status === 'approved'
            && $this->isAccessible();
    }
}
