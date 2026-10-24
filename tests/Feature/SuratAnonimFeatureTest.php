<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BlockedContact;
use App\Models\DeliveryJob;
use App\Models\Letter;
use App\Models\Reply;
use App\Models\ReplyThread;
use App\Models\Report;
use App\Models\User;
use App\Services\PrivacyService;
use App\Services\SecretLinkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SuratAnonimFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_letter_with_secret_link()
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/letters', [
            'body' => 'Halo ini adalah surat rahasia untukmu yang sangat berharga.',
            'target_type' => 'manual',
            'target_label' => '@sahabat',
            'visibility' => 'private',
            'music_url' => 'https://open.spotify.com/track/4cOdK2wGLETKBW3PvgPWqT',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('letters', [
            'sender_user_id' => $user->id,
            'target_label' => '@sahabat',
            'visibility' => 'private',
        ]);

        $letter = Letter::where('sender_user_id', $user->id)->first();
        $this->assertNotNull($letter->token_hash);
        $this->assertNull($letter->target_contact_hmac);
    }

    public function test_recipient_can_access_letter_anonymously_via_secret_token()
    {
        $user = User::factory()->create();
        $token = SecretLinkService::generateToken();
        $tokenHash = SecretLinkService::hashToken($token);

        $letter = Letter::create([
            'sender_user_id' => $user->id,
            'body' => 'Pesan rahasia tanpa identitas pengirim.',
            'visibility' => 'unlisted',
            'moderation_status' => 'approved',
            'token_hash' => $tokenHash,
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        $response = $this->get('/buka/' . $token);
        $response->assertStatus(200);
        $response->assertSee('Pesan rahasia tanpa identitas pengirim.');
        $response->assertDontSee($user->email);
        $response->assertDontSee($user->name);

        $letter->refresh();
        $this->assertEquals(1, $letter->view_count);
    }

    public function test_expired_or_invalid_token_shows_neutral_unavailable_page()
    {
        $response = $this->get('/buka/token_palsu_yang_tidak_ada_123');
        $response->assertStatus(404);
        $response->assertSee('Surat Tidak Tersedia');

        $user = User::factory()->create();
        $token = SecretLinkService::generateToken();
        $letter = Letter::create([
            'sender_user_id' => $user->id,
            'body' => 'Surat lampau.',
            'visibility' => 'unlisted',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken($token),
            'expires_at' => Carbon::now()->subDay(),
            'view_count' => 0,
        ]);

        $responseExpired = $this->get('/buka/' . $token);
        $responseExpired->assertStatus(404);
        $responseExpired->assertSee('Surat Tidak Tersedia');
    }

    public function test_recipient_can_start_anonymous_reply_thread()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $token = SecretLinkService::generateToken();

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Pertanyaan rahasia.',
            'visibility' => 'unlisted',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken($token),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        // 1. Recipient reads letter via token
        $this->get('/buka/' . $token);

        // 2. Recipient initiates reply
        $initiateResponse = $this->actingAs($recipient)->get('/letters/' . $letter->id . '/reply');
        $initiateResponse->assertRedirect();

        $thread = ReplyThread::where('letter_id', $letter->id)->first();
        $this->assertNotNull($thread);

        // 3. Recipient submits a reply in thread
        $replyResponse = $this->actingAs($recipient)->post('/threads/' . $thread->id, [
            'body' => 'Ini jawaban anonim saya kembali kepadamu.',
        ]);

        $replyResponse->assertRedirect();
        $this->assertDatabaseHas('replies', [
            'thread_id' => $thread->id,
            'author_user_id' => $recipient->id,
            'body' => 'Ini jawaban anonim saya kembali kepadamu.',
        ]);

        // 4. View thread: sender cannot see recipient's email/name
        $senderThreadResponse = $this->actingAs($sender)->get('/threads/' . $thread->id);
        $senderThreadResponse->assertStatus(200);
        $senderThreadResponse->assertSee('Ini jawaban anonim saya kembali kepadamu.');
        $senderThreadResponse->assertDontSee($recipient->email);

        // Recipient cannot see sender's email/name
        $recipientThreadResponse = $this->actingAs($recipient)->get('/threads/' . $thread->id);
        $recipientThreadResponse->assertStatus(200);
        $recipientThreadResponse->assertDontSee($sender->email);
    }

    public function test_letter_is_hidden_when_report_threshold_is_reached()
    {
        $sender = User::factory()->create();
        $token = SecretLinkService::generateToken();

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Surat yang melanggar aturan komunitas.',
            'visibility' => 'unlisted',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken($token),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        // Submit 3 reports
        for ($i = 0; $i < 3; $i++) {
            $reporter = User::factory()->create();
            $this->actingAs($reporter)->post('/reports', [
                'content_type' => 'letter',
                'content_id' => (string)$letter->id,
                'category' => 'harassment',
                'details' => 'Mengandung unsur pelecehan',
            ]);
        }

        $letter->refresh();
        $this->assertEquals('under_review', $letter->moderation_status);

        // Accessing the letter now should be unavailable to public/recipient
        $response = $this->get('/buka/' . $token);
        $response->assertStatus(404);
        $response->assertSee('Surat Tidak Tersedia');
    }

    public function test_blocked_target_cannot_be_sent_emails()
    {
        Mail::fake();
        $sender = User::factory()->create();
        $targetEmail = 'korban@example.com';
        $hmac = PrivacyService::hashTarget($targetEmail);

        // Target enters blocklist
        BlockedContact::create([
            'contact_hmac' => $hmac,
            'reason_code' => 'recipient_opt_out',
            'source' => 'recipient_action',
        ]);

        $response = $this->actingAs($sender)->post('/letters', [
            'body' => 'Mencoba mengirim ke email yang memblokir.',
            'target_type' => 'email',
            'target_email' => $targetEmail,
            'visibility' => 'private',
        ]);

        $response->assertRedirect();

        // Ensure no delivery job was queued for blocked HMAC
        $this->assertDatabaseMissing('delivery_jobs', [
            'destination_hmac' => $hmac,
        ]);

        Mail::assertNothingQueued();
    }

    public function test_admin_can_approve_pending_public_letter_for_feed()
    {
        $author = User::factory()->create();
        $admin = User::factory()->create();
        AdminUser::create([
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);

        $letter = Letter::create([
            'sender_user_id' => $author->id,
            'body' => 'Puisi indah untuk diterbitkan di feed publik.',
            'visibility' => 'public_pending',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        // Approve letter as admin
        $this->actingAs($admin)->post('/admin/letters/' . $letter->id . '/approve');

        $letter->refresh();
        $this->assertEquals('public', $letter->visibility);
        $this->assertNotNull($letter->published_at);

        // Check feed
        $feedResponse = $this->get('/feed');
        $feedResponse->assertStatus(200);
        $feedResponse->assertSee('Puisi indah untuk diterbitkan di feed publik.');
    }

    public function test_purge_expired_command_cleans_old_letters()
    {
        $author = User::factory()->create();
        $expiredLetter = Letter::create([
            'sender_user_id' => $author->id,
            'body' => 'Konten kadaluwarsa yang harus dihapus.',
            'visibility' => 'unlisted',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->subDays(2),
            'view_count' => 0,
        ]);

        $this->artisan('letters:purge-expired')->assertExitCode(0);

        $expiredLetter->refresh();
        $this->assertNull($expiredLetter->body);
        $this->assertNotNull($expiredLetter->deleted_at);
    }
}
