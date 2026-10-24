<?php

namespace App\Services;

use App\Models\Letter;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SecretLinkService
{
    /**
     * Generate a cryptographic URL-safe random token (21 chars, nano-id style).
     */
    public static function generateToken(): string
    {
        // 21 characters from URL-safe charset
        $bytes = random_bytes(16);
        $base64 = base64_encode($bytes);
        $urlSafe = strtr(rtrim($base64, '='), '+/', '-_');
        return substr($urlSafe, 0, 21);
    }

    /**
     * Hash token for secure storage (SHA-256).
     */
    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', trim($rawToken));
    }

    /**
     * Find active accessible letter by raw token.
     */
    public static function findByRawToken(string $rawToken): ?Letter
    {
        $hash = self::hashToken($rawToken);

        $letter = Letter::where('token_hash', $hash)->first();

        if (!$letter) {
            return null;
        }

        if (!$letter->isAccessible()) {
            return null;
        }

        return $letter;
    }

    /**
     * Revoke access to a letter.
     */
    public function revoke(Letter $letter): void
    {
        $letter->update([
            'revoked_at' => Carbon::now(),
        ]);
    }
}
