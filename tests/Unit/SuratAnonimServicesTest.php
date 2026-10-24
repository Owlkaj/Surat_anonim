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
