<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_id',
        'channel',
        'destination_hmac',
        'destination_encrypted',
        'status',
        'attempt_count',
        'next_attempt_at',
        'idempotency_key',
        'provider_message_id',
        'last_error_code',
        'purge_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'next_attempt_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class, 'letter_id');
    }
}
