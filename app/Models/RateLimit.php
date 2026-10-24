<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_hmac',
        'action',
        'window_start',
        'count',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'window_start' => 'datetime',
            'expires_at' => 'datetime',
            'count' => 'integer',
        ];
    }
}
