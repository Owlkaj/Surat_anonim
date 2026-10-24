<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_type',
        'content_id',
        'reporter_user_id',
        'reporter_hmac',
        'category',
        'details',
        'status',
        'resolved_at',
        'purge_at',
    ];

    protected function casts(): array
    {
        return [
            'content_id' => 'integer',
            'resolved_at' => 'datetime',
            'purge_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class, 'content_id');
    }

    public function reply(): BelongsTo
    {
        return $this->belongsTo(Reply::class, 'content_id');
    }
}
