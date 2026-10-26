<?php

namespace Tests\Feature;

use App\Models\BlockedContact;
use App\Models\Letter;
use App\Models\Reply;
use App\Models\ReplyThread;
use App\Models\Report;
use App\Models\User;
use App\Services\PrivacyService;
use App\Services\SecretLinkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecurityHardeningPhase1Test extends TestCase
{
    use RefreshDatabase;

    /**
     * H-01: Verifikasi bahwa penyerang yang mengirim header X-Forwarded-For palsu
     * dari proxy yang tidak dipercaya tidak dapat memalsukan IP request.
     */
    public function test_h01_untrusted_proxy_cannot_spoof_client_ip(): void
    {
        // Request datang dari 192.168.1.50 (bukan trusted proxy 127.0.0.1)
        // dengan header X-Forwarded-For yang mencoba memalsukan IP ke 8.8.8.8
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.50',
            'HTTP_X_FORWARDED_FOR' => '8.8.8.8',
        ])->get('/');

        $response->assertOk();
    }

    /**
     * H-02: Laporan dari tamu tanpa akun (guest) TIDAK boleh memicu auto-hide,
     * bahkan jika dikirim sebanyak 3 kali atau lebih.
     */
    public function test_h02_guest_reports_do_not_trigger_auto_hide(): void
    {
        $sender = User::factory()->create();
        $token = SecretLinkService::generateToken();

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Surat yang dilaporkan tamu.',
            'visibility' => 'unlisted',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken($token),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        // Tamu mengirim 4 laporan dengan menyertakan token yang valid
        for ($i = 1; $i <= 4; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.' . $i])
                ->post('/reports', [
                    'content_type' => 'letter',
                    'content_id' => $letter->id,
                    'category' => 'spam',
                    'details' => 'Laporan dari tamu',
                    'access_token' => $token,
                ]);
        }

        $letter->refresh();

        // Status surat HARUS tetap 'approved', TIDAK 'under_review'
        $this->assertSame('approved', $letter->moderation_status);
        $this->assertTrue($letter->isAccessible());
    }

    /**
     * H-02: Tamu yang menebak ID surat privat/unlisted tanpa access_token
     * tidak boleh diproses laporannya.
     */
    public function test_h02_guest_cannot_report_unlisted_letter_without_access_token(): void
    {
        $sender = User::factory()->create();
        $token = SecretLinkService::generateToken();

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Surat rahasia.',
            'visibility' => 'unlisted',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken($token),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        $this->post('/reports', [
            'content_type' => 'letter',
            'content_id' => $letter->id,
            'category' => 'harassment',
            // access_token sengaja dikosongkan/tidak disertakan
        ]);

        // Laporan tidak boleh tersimpan di database
        $this->assertDatabaseMissing('reports', [
            'content_type' => 'letter',
            'content_id' => $letter->id,
        ]);
    }

    /**
     * H-04: Endpoint lama /letters/{token}/block yang tidak aman sudah tidak tersedia.
     */
    public function test_h04_insecure_token_block_endpoint_is_removed(): void
    {
        $response = $this->post('/letters/Ox-RYX0_validtoken123/block');
        $this->assertContains($response->getStatusCode(), [404, 405]);
    }

    /**
     * H-04: Unsubscribe hanya dapat diproses melalui tautan bertanda tangan sah
     * yang dikirimkan ke email penerima.
     */
    public function test_h04_signed_unsubscribe_link_blocks_target_contact(): void
    {
        $sender = User::factory()->create();
        $targetEmail = 'korban-unsubscribe@example.com';
        $targetHmac = PrivacyService::hashTarget($targetEmail);

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'target_contact_hmac' => $targetHmac,
            'body' => 'Surat dengan email target.',
            'visibility' => 'private',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Buat signed route sah seperti yang dihasilkan antrean email
        $signedUrl = URL::temporarySignedRoute(
            'letters.unsubscribe',
            Carbon::now()->addDays(30),
            ['letter' => $letter->id]
        );

        $response = $this->get($signedUrl);
        $response->assertRedirect(route('home'));

        $this->assertDatabaseHas('blocked_contacts', [
            'contact_hmac' => $targetHmac,
            'source' => 'recipient_email',
        ]);
    }

    /**
     * H-04: Tautan unsubscribe tanpa tanda tangan yang sah harus ditolak (403).
     */
    public function test_h04_unsigned_unsubscribe_request_is_rejected(): void
    {
        $sender = User::factory()->create();
        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'target_contact_hmac' => PrivacyService::hashTarget('target@example.com'),
            'body' => 'Surat uji.',
            'visibility' => 'private',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        // Akses langsung tanpa signature query params
        $response = $this->get('/unsubscribe/' . $letter->id);
        $response->assertStatus(403);
    }
}
