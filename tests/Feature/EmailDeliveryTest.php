<?php

namespace Tests\Feature;

use App\Jobs\SendLetterNotificationJob;
use App\Mail\LetterNotificationMail;
use App\Models\DeliveryJob;
use App\Models\Letter;
use App\Models\User;
use App\Services\PrivacyService;
use App\Services\SecretLinkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Tests\TestCase;

class EmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function postEmailLetter(User $user, string $email = 'penerima@example.com')
    {
        return $this->actingAs($user)->post('/letters', [
            'body' => 'Surat yang dikirim otomatis lewat email.',
            'target_type' => 'email',
            'target_email' => $email,
            'visibility' => 'private',
        ]);
    }

    public function test_email_target_automatically_sends_notification_with_secret_link_only()
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->postEmailLetter($user)->assertRedirect();

        $delivery = DeliveryJob::first();
        $this->assertSame('sent', $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
        $this->assertNull($delivery->destination_encrypted, 'Kontak polos harus dihapus setelah terkirim');

        Mail::assertSent(LetterNotificationMail::class, function (LetterNotificationMail $mail) use ($user) {
            $html = $mail->render();

            return $mail->hasTo('penerima@example.com')
                && str_contains($mail->secretUrl, '/letters/')
                && !str_contains($html, 'Surat yang dikirim otomatis lewat email.') // FR-08: tanpa isi surat
                && !str_contains($html, $user->email)                                 // identitas pengirim tidak bocor
                && !str_contains($html, $user->name);
        });
    }

    public function test_queue_payload_does_not_contain_plain_email_address()
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->postEmailLetter($user, 'rahasia@example.com');

        Queue::assertPushed(SendLetterNotificationJob::class, function (SendLetterNotificationJob $job) {
            return !str_contains(serialize($job), 'rahasia@example.com');
        });
    }

    public function test_job_is_idempotent_when_already_sent()
    {
        Mail::fake();
        $delivery = $this->makeDelivery(['status' => 'sent']);

        (new SendLetterNotificationJob($delivery->id, 'http://localhost/letters/x'))->handle(new PrivacyService());

        Mail::assertNothingSent();
        $this->assertSame(0, $delivery->fresh()->attempt_count);
    }

    public function test_permanent_smtp_bounce_is_not_retried()
    {
        Mail::shouldReceive('to->send')->andThrow(new UnexpectedResponseException('Mailbox unavailable', 550));
        $delivery = $this->makeDelivery();

        (new SendLetterNotificationJob($delivery->id, 'http://localhost/letters/x'))->handle(new PrivacyService());

        $delivery->refresh();
        $this->assertSame('failed_permanent', $delivery->status);
        $this->assertSame('smtp_550', $delivery->last_error_code);
    }

    public function test_temporary_failure_is_marked_retryable_and_rethrown()
    {
        Mail::shouldReceive('to->send')->andThrow(new UnexpectedResponseException('Try again later', 421));
        $delivery = $this->makeDelivery();

        try {
            (new SendLetterNotificationJob($delivery->id, 'http://localhost/letters/x'))->handle(new PrivacyService());
            $this->fail('Exception harus dilempar ulang agar queue melakukan retry');
        } catch (UnexpectedResponseException $e) {
            // expected
        }

        $delivery->refresh();
        $this->assertSame('failed_retryable', $delivery->status);
        $this->assertNotNull($delivery->next_attempt_at);
    }

    public function test_max_three_notifications_per_target_per_day()
    {
        Mail::fake();
        $sender = User::factory()->create();
        $other = User::factory()->create();

        // 3 surat dari pengirim pertama, 1 dari pengirim lain ke target yang sama
        for ($i = 0; $i < 3; $i++) {
            $this->postEmailLetter($sender, 'target@example.com');
        }
        $this->postEmailLetter($other, 'target@example.com')->assertRedirect();

        $this->assertSame(4, Letter::count(), 'Surat tetap dibuat (tidak membocorkan status target)');
        $this->assertSame(3, DeliveryJob::count());
        Mail::assertSentCount(3);
    }

    public function test_notification_mail_has_text_alternative_and_unsubscribe_header(): void
    {
        config(['mail.unsubscribe_url' => 'https://example.com/unsubscribe']);

        $mail = new LetterNotificationMail('https://example.com/letters/token-uji');

        $this->assertSame('emails.letter_notification', $mail->content()->view);
        $this->assertSame('emails.letter_notification_text', $mail->content()->text);
        $this->assertSame(
            ['List-Unsubscribe' => '<https://example.com/unsubscribe>'],
            $mail->headers()->text
        );
    }

    private function makeDelivery(array $overrides = []): DeliveryJob
    {
        $user = User::factory()->create();
        $letter = Letter::create([
            'sender_user_id' => $user->id,
            'body' => 'x',
            'visibility' => 'private',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        return DeliveryJob::create(array_merge([
            'letter_id' => $letter->id,
            'channel' => 'email',
            'destination_hmac' => PrivacyService::hashTarget('t@example.com'),
            'destination_encrypted' => (new PrivacyService())->encryptContact('t@example.com'),
            'status' => 'queued',
            'attempt_count' => 0,
            'idempotency_key' => 'test_' . uniqid(),
        ], $overrides));
    }
}
