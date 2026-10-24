<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\Reply;
use App\Models\ReplyThread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnonymousReplyService
{
    /**
     * Get or create thread associated with a letter when a recipient initiates reply.
     */
    public function getOrCreateThread(Letter $letter, User $recipient): ReplyThread
    {
        return DB::transaction(function () use ($letter, $recipient) {
            $thread = ReplyThread::firstOrCreate(
                ['letter_id' => $letter->id],
                [
                    'recipient_user_id' => $recipient->id,
                    'status' => 'active',
                    'expires_at' => $letter->expires_at ?? Carbon::now()->addDays(30),
                ]
            );

            // If thread existed without recipient assigned yet, assign current recipient
            if ($thread->recipient_user_id === null && $letter->sender_user_id !== $recipient->id) {
                $thread->update(['recipient_user_id' => $recipient->id]);
            }

            return $thread;
        });
    }

    /**
     * Determine if a user is authorized to participate in this thread.
     */
    public function isAuthorized(ReplyThread $thread, User $user): bool
    {
        // Must be either sender of the letter or recipient of the thread
        if ($thread->letter->sender_user_id === $user->id) {
            return true;
        }

        if ($thread->recipient_user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Add a reply to the thread.
     */
    public function addReply(
        ReplyThread $thread,
        User $author,
        string $body,
        string $moderationStatus = 'approved'
    ): Reply {
        return Reply::create([
            'thread_id' => $thread->id,
            'author_user_id' => $author->id,
            'body' => trim($body),
            'moderation_status' => $moderationStatus,
            'purge_at' => $thread->expires_at,
        ]);
    }
}
