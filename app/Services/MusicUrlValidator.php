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
     * Determine provider, playback strategy, and safe embed/link format.
     *
     * Flag yang dikembalikan:
     * - autoplay: provider mendukung pemutaran otomatis dari halaman induk.
     * - volume: provider mengizinkan pengaturan volume dari halaman induk.
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
            $type = 'track';
            $id = null;

            if (preg_match('#open\.spotify\.com/(?:intl-[a-z-]+/)?(track|album|playlist|episode|show)/([a-zA-Z0-9]+)#i', $url, $matches)) {
                $type = $matches[1];
                $id = $matches[2];
            }

            return [
                'provider' => 'Spotify',
                'url' => $url,
                'embed_url' => $id ? "https://open.spotify.com/embed/{$type}/{$id}?utm_source=generator&theme=0" : null,
                'player' => 'spotify',
                'uri' => $id ? "spotify:{$type}:{$id}" : null,
                'autoplay' => $id !== null,
                'volume' => false,
                'icon' => 'spotify',
            ];
        }

        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
            $videoId = $this->extractYouTubeId($url, $host);

            return [
                'provider' => 'YouTube',
                'url' => $url,
                'embed_url' => $videoId ? "https://www.youtube-nocookie.com/embed/{$videoId}?enablejsapi=1&playsinline=1&rel=0&autoplay=1" : null,
                'player' => 'youtube',
                'youtube_id' => $videoId,
                'uri' => null,
                'autoplay' => $videoId !== null,
                'volume' => true,
                'icon' => 'youtube',
            ];
        }

        if (str_contains($host, 'apple.com')) {
            return [
                'provider' => 'Apple Music',
                'url' => $url,
                'embed_url' => str_replace('music.apple.com', 'embed.music.apple.com', $url),
                'player' => 'apple',
                'uri' => null,
                'autoplay' => false,
                'volume' => false,
                'icon' => 'apple',
            ];
        }

        if (str_contains($host, 'soundcloud.com')) {
            $embedUrl = 'https://w.soundcloud.com/player/?url=' . rawurlencode($url)
                . '&auto_play=true&hide_related=true&show_comments=false'
                . '&show_user=false&show_reposts=false&visual=false&show_teaser=false';

            return [
                'provider' => 'SoundCloud',
                'url' => $url,
                'embed_url' => $embedUrl,
                'player' => 'soundcloud',
                'uri' => null,
                'autoplay' => true,
                'volume' => true,
                'icon' => 'soundcloud',
            ];
        }

        return [
            'provider' => 'Music Link',
            'url' => $url,
            'embed_url' => null,
            'player' => 'link',
            'uri' => null,
            'autoplay' => false,
            'volume' => false,
            'icon' => 'music',
        ];
    }

    /**
     * Ambil ID video YouTube dari berbagai bentuk tautan yang diizinkan.
     */
    protected function extractYouTubeId(string $url, string $host): ?string
    {
        if (str_contains($host, 'youtu.be')) {
            $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
            $id = explode('/', $path)[0] ?? '';

            return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        // youtube.com/shorts/{id} dan youtube.com/embed/{id}
        if (preg_match('#^/(?:shorts|embed|live)/([A-Za-z0-9_-]{11})#', $path, $matches)) {
            return $matches[1];
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
        $id = isset($params['v']) ? (string) $params['v'] : '';

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
    }
}
