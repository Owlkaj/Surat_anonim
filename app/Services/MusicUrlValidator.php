<?php

namespace App\Services;

class MusicUrlValidator
{
    /**
     * Allowed music service hosts.
     */
    protected array $allowedHosts = [
        'open.spotify.com',
        'spotify.com',
        'youtube.com',
        'www.youtube.com',
        'youtu.be',
        'music.youtube.com',
        'music.apple.com',
        'soundcloud.com',
        'www.soundcloud.com',
    ];

    /**
     * Validate music URL against HTTPS and host allowlist.
     */
    public function isValid(?string $url): bool
    {
        if (empty($url)) {
            return true; // Optional field
        }

        $url = trim($url);

        // Must start strictly with https://
        if (!str_starts_with(strtolower($url), 'https://')) {
            return false;
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return false;
        }

        $host = strtolower($parsed['host']);

        // Check if host matches allowlist or is a subdomain of allowlist
        foreach ($this->allowedHosts as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine provider and safe embed/link format without autoplay.
     */
    public function getMediaInfo(?string $url): ?array
    {
        if (!$this->isValid($url) || empty($url)) {
            return null;
        }

        $url = trim($url);
        $host = parse_url($url, PHP_URL_HOST);
        $host = strtolower((string)$host);

        if (str_contains($host, 'spotify.com')) {
            // Transform Spotify link to embed URL if possible
            // https://open.spotify.com/track/{id} -> https://open.spotify.com/embed/track/{id}
            $embedUrl = preg_replace('/open\.spotify\.com\/(track|album|playlist|episode)\/([a-zA-Z0-9]+)/', 'open.spotify.com/embed/$1/$2', $url);
            return [
                'provider' => 'Spotify',
                'url' => $url,
                'embed_url' => $embedUrl !== $url ? $embedUrl : null,
                'icon' => 'spotify',
            ];
        }

        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
            $videoId = null;
            if (str_contains($host, 'youtu.be')) {
                $videoId = ltrim((string)parse_url($url, PHP_URL_PATH), '/');
            } else {
                parse_str((string)parse_url($url, PHP_URL_QUERY), $params);
                $videoId = $params['v'] ?? null;
            }

            return [
                'provider' => 'YouTube',
                'url' => $url,
                'embed_url' => $videoId ? "https://www.youtube-nocookie.com/embed/{$videoId}?autoplay=0" : null,
                'icon' => 'youtube',
            ];
        }

        if (str_contains($host, 'apple.com')) {
            return [
                'provider' => 'Apple Music',
                'url' => $url,
                'embed_url' => null,
                'icon' => 'apple',
            ];
        }

        if (str_contains($host, 'soundcloud.com')) {
            return [
                'provider' => 'SoundCloud',
                'url' => $url,
                'embed_url' => null,
                'icon' => 'soundcloud',
            ];
        }

        return [
            'provider' => 'Music Link',
            'url' => $url,
            'embed_url' => null,
            'icon' => 'music',
        ];
    }
}
