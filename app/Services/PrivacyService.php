<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class PrivacyService
{
    /**
     * Compute a consistent HMAC-SHA-256 for a contact identifier (e.g. email) using app key.
     */
    public static function computeContactHmac(?string $identifier): ?string
    {
        if (empty($identifier)) {
            return null;
        }

        $normalized = strtolower(trim($identifier));
        $salt = config('app.key');

        return hash_hmac('sha256', $normalized, $salt);
    }

    /**
     * Alias for computeContactHmac.
     */
    public static function hashTarget(?string $identifier): ?string
    {
        return self::computeContactHmac($identifier);
    }

    /**
     * Mask an email address for safe UI display (e.g., "b***@domain.com").
     */
    public function maskEmail(?string $email): string
    {
        if (empty($email)) {
            return 'Penerima';
        }

        $email = trim($email);
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return 'Target tersamarkan';
        }

        $name = $parts[0];
        $domain = $parts[1];

        $firstChar = substr($name, 0, 1);
        return $firstChar . '***@' . $domain;
    }

    /**
     * Encrypt sensitive temporary contact information.
     */
    public function encryptContact(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Crypt::encryptString(trim($value));
    }

    /**
     * Decrypt sensitive temporary contact information.
     */
    public function decryptContact(?string $encrypted): ?string
    {
        if (empty($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }
}
