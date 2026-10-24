<?php

namespace App\Http\Controllers;

use App\Models\BlockedContact;
use App\Models\Letter;
use App\Services\AnonymousReplyService;
use App\Services\MusicUrlValidator;
use App\Services\SecretLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LetterAccessController extends Controller
{
    public function __construct(
        protected SecretLinkService $secretLinkService,
        protected MusicUrlValidator $musicValidator,
        protected AnonymousReplyService $replyService
    ) {}

    /**
     * Read an anonymous letter via its secret token link.
     */
    public function show(string $token)
    {
        $letter = $this->secretLinkService->findByRawToken($token);

        if (!$letter) {
            // Anti-enumeration: neutral response without leaking why it's unavailable
            return response()->view('letters.unavailable', [], 404);
        }

        // Deduplikasi view counter per jendela waktu (bukan permanen selamanya).
        $viewSessionKey = 'viewed_letter_'.$letter->id;
        $lastViewedAt = Session::get($viewSessionKey);
        $shouldCountView = true;

        if ($lastViewedAt instanceof \DateTimeInterface) {
            $shouldCountView = now()->diffInMinutes($lastViewedAt) >= 30;
        }

        if ($shouldCountView) {
            $letter->increment('view_count');
            Session::put($viewSessionKey, now());
        }

        $mediaInfo = $this->musicValidator->getMediaInfo($letter->music_url);

        // Store the active letter token in session so after login/register, user can reply
        Session::put('active_letter_token', $token);
        Session::put('active_letter_id', $letter->id);

        $thread = $letter->thread()->with('visibleReplies')->first();

        return view('letters.read', compact('letter', 'mediaInfo', 'token', 'thread'));
    }

    /**
     * Opt-out / block contact for the letter.
     */
    public function block(Request $request, string $token)
    {
        $letter = $this->secretLinkService->findByRawToken($token);

        if ($letter && $letter->target_contact_hmac) {
            BlockedContact::firstOrCreate(
                ['contact_hmac' => $letter->target_contact_hmac],
                [
                    'owner_user_id' => Auth::id(),
                    'reason_code' => 'recipient_opt_out',
                    'source' => 'recipient_action',
                ]
            );
        }

        return redirect()->route('home')->with('success', 'Kamu berhasil berhenti menerima dan memblokir pengiriman berikutnya terkait pesan ini.');
    }
}
