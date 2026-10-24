<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class LetterDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_permanently_delete_letter_with_related_data(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $letter = Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Surat yang akan dihapus permanen.',
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

        $visibleReply = Reply::create([
            'thread_id' => $thread->id,
            'author_user_id' => $recipient->id,
            'body' => 'Balasan yang masih aktif.',
            'moderation_status' => 'approved',
        ]);

        $trashedReply = Reply::create([
            'thread_id' => $thread->id,
            'author_user_id' => $recipient->id,
            'body' => 'Balasan yang sudah dihapus lunak.',
            'moderation_status' => 'approved',
        ]);
        $trashedReply->delete();

        $delivery = DeliveryJob::create([
            'letter_id' => $letter->id,
            'channel' => 'email',
            'destination_hmac' => PrivacyService::hashTarget('target@example.com'),
            'destination_encrypted' => (new PrivacyService())->encryptContact('target@example.com'),
            'status' => 'sent',
            'attempt_count' => 1,
            'idempotency_key' => 'delete_test_'.$letter->id,
        ]);

        $letterReport = Report::create([
            'content_type' => 'letter',
            'content_id' => $letter->id,
            'reporter_user_id' => $recipient->id,
            'category' => 'spam',
            'status' => 'pending',
        ]);

        $replyReport = Report::create([
            'content_type' => 'reply',
            'content_id' => $visibleReply->id,
            'reporter_user_id' => $sender->id,
            'category' => 'harassment',
            'status' => 'pending',
        ]);

        $this->actingAs($sender)
            ->delete('/my-letters/'.$letter->id)
            ->assertRedirect(route('letters.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('letters', ['id' => $letter->id]);
        $this->assertDatabaseMissing('reply_threads', ['id' => $thread->id]);
        $this->assertDatabaseMissing('replies', ['id' => $visibleReply->id]);
        $this->assertDatabaseMissing('replies', ['id' => $trashedReply->id]);
        $this->assertDatabaseMissing('delivery_jobs', ['id' => $delivery->id]);
        $this->assertDatabaseMissing('reports', ['id' => $letterReport->id]);
        $this->assertDatabaseMissing('reports', ['id' => $replyReport->id]);
    }

    public function test_user_cannot_delete_another_users_letter(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $letter = Letter::create([
            'sender_user_id' => $owner->id,
            'body' => 'Surat milik orang lain.',
            'visibility' => 'private',
            'moderation_status' => 'approved',
            'token_hash' => SecretLinkService::hashToken(SecretLinkService::generateToken()),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        $this->actingAs($other)
            ->delete('/my-letters/'.$letter->id)
            ->assertForbidden();

        $this->assertDatabaseHas('letters', ['id' => $letter->id]);
    }

    public function test_removed_target_and_visibility_options_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/letters', [
            'body' => 'Mencoba memakai opsi yang sudah dihapus.',
            'target_type' => 'none',
            'visibility' => 'unlisted',
        ]);

        $response->assertSessionHasErrors(['target_type', 'visibility']);
        $this->assertSame(0, Letter::count());
    }

    public function test_letter_page_uses_floating_music_player_without_embedded_player_box(): void
    {
        $sender = User::factory()->create();
        $token = SecretLinkService::generateToken();

        Letter::create([
            'sender_user_id' => $sender->id,
            'body' => 'Surat dengan lagu pengiring.',
            'visibility' => 'private',
            'moderation_status' => 'approved',
            'music_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'token_hash' => SecretLinkService::hashToken($token),
            'expires_at' => Carbon::now()->addDays(30),
            'view_count' => 0,
        ]);

        $response = $this->get('/buka/'.$token);

        $response->assertOk();
        $response->assertSee('data-music-player', false);
        $response->assertSee('data-music-toggle', false);
        $response->assertSee('data-music-volume', false);
        $response->assertDontSee('aspect-video', false);
    }
}
