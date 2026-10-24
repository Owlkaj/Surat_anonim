<?php

namespace Tests\Feature;

use App\Jobs\SendLetterNotificationJob;
use App\Models\AdminUser;
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
use Illuminate\Support\Str;
use Tests\TestCase;

class RefinementTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_initiation_requires_valid_secret_link_session(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Surat privat yang tidak boleh dibalas tanpa link.',
            'visibility' => 'unlisted',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        $this->actingAs($recipient)
            ->get('/letters/'.$letter->id.'/reply')
            ->assertStatus(404)
            ->assertSee('Surat Tidak Tersedia');
    }

    public function test_under_review_reply_is_hidden_from_participants(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Surat untuk menguji moderasi balasan.',
            'visibility' => 'private',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        $thread = ReplyThread::create([
            'letter_id' => $letter->id,
            'recipient_user_id' => $recipient->id,
            'status' => 'active',
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        Reply::create([
            'thread_id' => $thread->id,
            'author_user_id' => $recipient->id,
            'body' => 'Pesan ini sedang ditahan oleh moderator.',
            'moderation_status' => 'under_review',
        ]);

        $this->actingAs($sender)
            ->get('/threads/'.$thread->id)
            ->assertOk()
            ->assertSee('sedang ditinjau moderator')
            ->assertDontSee('Pesan ini sedang ditahan oleh moderator.');
    }

    public function test_letter_index_supports_search_and_visibility_filter(): void
    {
        $user = User::factory()->create();

        Letter::create([
            'sender_user_id' => $user->id,
            'target_label' => '@sahabat',
            'body' => 'Pesan rahasia untuk sahabat lama.',
            'visibility' => 'private',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        Letter::create([
            'sender_user_id' => $user->id,
            'target_label' => '@kantor',
            'body' => 'Catatan kerja yang berbeda.',
            'visibility' => 'unlisted',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        $this->actingAs($user)
            ->get('/letters?q=sahabat&visibility=private')
            ->assertOk()
            ->assertSee('Pesan rahasia untuk sahabat lama.')
            ->assertDontSee('Catatan kerja yang berbeda.');
    }

    public function test_public_feed_view_count_is_deduplicated_in_session(): void
    {
        $sender = User::factory()->create();

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Surat publik untuk uji hitungan pembacaan.',
            'visibility' => 'public',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'published_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        $this->get('/feed/'.$letter->id)->assertOk();
        $this->get('/feed/'.$letter->id)->assertOk();

        $this->assertSame(1, $letter->fresh()->view_count);
    }

    public function test_dismissing_last_report_restores_auto_hidden_letter(): void
    {
        $sender = User::factory()->create();
        $admin = User::factory()->create();
        AdminUser::create([
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Konten yang sempat ditahan karena laporan.',
            'visibility' => 'unlisted',
            'moderation_status' => 'under_review',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        $reports = collect(range(1, 3))->map(function (int $index) use ($letter) {
            $reporter = User::factory()->create();

            return Report::create([
                'content_type' => 'letter',
                'content_id' => $letter->id,
                'reporter_user_id' => $reporter->id,
                'reporter_hmac' => 'reporter-'.$index,
                'category' => 'harassment',
                'status' => 'pending',
            ]);
        });

        foreach ($reports as $report) {
            $this->actingAs($admin)
                ->post('/admin/reports/'.$report->id.'/resolve', [
                    'action' => 'dismiss',
                    'reason' => 'tidak_melanggar',
                ])
                ->assertRedirect();
        }

        $this->assertSame('approved', $letter->fresh()->moderation_status);
        $this->assertSame(0, Report::whereIn('status', ['pending', 'under_review'])->count());
    }

    public function test_login_redirect_only_accepts_local_paths(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'redirect' => '/letters',
        ])->assertRedirect('/letters');

        $this->post('/logout');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'redirect' => 'https://example.com/phishing',
        ])->assertRedirect('/dashboard');
    }

    public function test_notification_is_not_sent_for_revoked_letter(): void
    {
        Mail::fake();

        $sender = User::factory()->create();
        $privacy = app(PrivacyService::class);

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Surat yang sudah dicabut sebelum email terkirim.',
            'visibility' => 'private',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
            'revoked_at' => Carbon::now(),
            'view_count' => 0,
        ]);

        $delivery = DeliveryJob::create([
            'letter_id' => $letter->id,
            'channel' => 'email',
            'destination_hmac' => PrivacyService::hashTarget('target@example.com'),
            'destination_encrypted' => $privacy->encryptContact('target@example.com'),
            'status' => 'queued',
            'attempt_count' => 0,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        (new SendLetterNotificationJob($delivery->id, 'https://example.com/buka/token'))->handle($privacy);

        $this->assertSame('failed_permanent', $delivery->fresh()->status);
        $this->assertSame('letter_unavailable', $delivery->fresh()->last_error_code);
        Mail::assertNothingSent();
    }
}
