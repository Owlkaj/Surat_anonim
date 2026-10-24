<?php

namespace Tests\Unit;

use App\Services\ModerationService;
use App\Services\MusicUrlValidator;
use App\Services\PrivacyService;
use App\Services\SecretLinkService;
use PHPUnit\Framework\TestCase;

class SuratAnonimServicesTest extends TestCase
{
    public function test_secret_link_service_generates_21_char_token(): void
    {
        $service = new SecretLinkService();
        $token = $service->generateToken();

        $this->assertEquals(21, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+$/', $token);

        $hash = $service->hashToken($token);
        $this->assertEquals(64, strlen($hash)); // SHA-256 hex length
    }

    public function test_music_url_validator_accepts_valid_providers(): void
    {
        $validator = new MusicUrlValidator();

        $this->assertTrue($validator->isValid('https://open.spotify.com/track/123456'));
        $this->assertTrue($validator->isValid('https://www.youtube.com/watch?v=abcdef'));
        $this->assertTrue($validator->isValid('https://youtu.be/abcdef'));
        $this->assertTrue($validator->isValid('https://soundcloud.com/artist/track'));
        $this->assertTrue($validator->isValid(null)); // Optional

        // Rejections
        $this->assertFalse($validator->isValid('http://open.spotify.com/track/123')); // HTTP rejected
        $this->assertFalse($validator->isValid('javascript:alert(1)'));
        $this->assertFalse($validator->isValid('https://random-malicious-site.com/track'));
    }

    public function test_music_media_info_exposes_player_capabilities(): void
    {
        $validator = new MusicUrlValidator();

        $youtube = $validator->getMediaInfo('https://youtu.be/dQw4w9WgXcQ');
        $this->assertSame('youtube', $youtube['player']);
        $this->assertSame('dQw4w9WgXcQ', $youtube['youtube_id']);
        $this->assertTrue($youtube['autoplay']);
        $this->assertTrue($youtube['volume']);

        $youtubeMusic = $validator->getMediaInfo('https://music.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertSame('dQw4w9WgXcQ', $youtubeMusic['youtube_id']);

        $soundcloud = $validator->getMediaInfo('https://soundcloud.com/artist/track');
        $this->assertSame('soundcloud', $soundcloud['player']);
        $this->assertStringContainsString('w.soundcloud.com/player', $soundcloud['embed_url']);
        $this->assertTrue($soundcloud['volume']);

        $spotify = $validator->getMediaInfo('https://open.spotify.com/track/4cOdK2wGLETKBW3PvgPWqT');
        $this->assertSame('spotify', $spotify['player']);
        $this->assertSame('spotify:track:4cOdK2wGLETKBW3PvgPWqT', $spotify['uri']);
        $this->assertFalse($spotify['volume'], 'Spotify tidak menyediakan kontrol volume dari halaman induk');

        $spotifyIntl = $validator->getMediaInfo('https://open.spotify.com/intl-id/track/4cOdK2wGLETKBW3PvgPWqT');
        $this->assertSame('spotify:track:4cOdK2wGLETKBW3PvgPWqT', $spotifyIntl['uri']);

        $apple = $validator->getMediaInfo('https://music.apple.com/id/album/contoh/123456');
        $this->assertSame('apple', $apple['player']);
        $this->assertFalse($apple['autoplay']);
        $this->assertSame('https://embed.music.apple.com/id/album/contoh/123456', $apple['embed_url']);
    }

    public function test_privacy_service_masks_email_safely(): void
    {
        $privacy = new PrivacyService();

        $this->assertEquals('j***@example.com', $privacy->maskEmail('john.doe@example.com'));
        $this->assertEquals('a***@gmail.com', $privacy->maskEmail('alice@gmail.com'));
        $this->assertEquals('Penerima', $privacy->maskEmail(null));
    }

    public function test_moderation_service_enforces_rules(): void
    {
        $moderation = new ModerationService();

        // Valid text
        $res = $moderation->inspectText('Halo apa kabar, ini surat rahasia untukmu.');
        $this->assertTrue($res['passed']);
        $this->assertFalse($res['flagged']);

        // Empty text
        $resEmpty = $moderation->inspectText('   ');
        $this->assertFalse($resEmpty['passed']);

        // High risk keywords
        $resThreat = $moderation->inspectText('Awas kau besok akan kubunuh');
        $this->assertTrue($resThreat['passed']);
        $this->assertTrue($resThreat['flagged']); // Flagged for review
    }
}
