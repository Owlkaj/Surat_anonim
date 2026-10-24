<?php

namespace App\Http\Controllers;

use App\Jobs\SendLetterNotificationJob;
use App\Models\BlockedContact;
use App\Models\DeliveryJob;
use App\Models\Letter;
use App\Models\RateLimit;
use App\Services\ModerationService;
use App\Services\MusicUrlValidator;
use App\Services\PrivacyService;
use App\Services\SecretLinkService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LetterController extends Controller
{
    public function __construct(
        protected SecretLinkService $secretLinkService,
        protected MusicUrlValidator $musicValidator,
        protected PrivacyService $privacyService,
        protected ModerationService $moderationService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Letter::where('sender_user_id', $user->id)
            ->with('latestDeliveryJob')
            ->with('thread.visibleReplies')
            ->orderBy('created_at', 'desc');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $query->where(function ($sub) use ($search) {
                $sub->where('body', 'like', '%'.$search.'%')
                    ->orWhere('target_label', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('visibility')
            && in_array($request->visibility, ['private', 'unlisted', 'public_pending', 'public'], true)) {
            $query->where('visibility', $request->visibility);
        }

        $letters = $query->paginate(15)->appends($request->query());

        // Stats for dashboard
        $totalLetters = Letter::where('sender_user_id', $user->id)->count();
        $openedLetters = Letter::where('sender_user_id', $user->id)->where('view_count', '>', 0)->count();
        $totalReplies = DB::table('replies')
            ->join('reply_threads', 'replies.thread_id', '=', 'reply_threads.id')
            ->join('letters', 'reply_threads.letter_id', '=', 'letters.id')
            ->where('letters.sender_user_id', $user->id)
            ->where('replies.author_user_id', '!=', $user->id)
            ->count();

        return view('letters.index', compact('letters', 'totalLetters', 'openedLetters', 'totalReplies'));
    }

    public function create()
    {
        return view('letters.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // 1. Rate limiting: max 5 letters per user per 24 hours
        $lettersLast24h = Letter::where('sender_user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();

        if ($lettersLast24h >= 5) {
            return back()
                ->withInput()
                ->withErrors(['body' => 'Batas pengiriman harian tercapai (maksimum 5 surat per 24 jam). Silakan coba lagi nanti.']);
        }

        // 2. Validate input
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'target_type' => ['required', 'in:email,manual,none'],
            'target_email' => ['nullable', 'required_if:target_type,email', 'email', 'max:255'],
            'target_label' => ['nullable', 'string', 'max:100'],
            'music_url' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', 'in:private,unlisted,public_pending'],
        ], [
            'body.required' => 'Isi surat wajib diisi.',
            'body.max' => 'Isi surat maksimal 5.000 karakter.',
            'target_type.required' => 'Pilih salah satu cara berbagi surat.',
            'target_email.required_if' => 'Alamat email target wajib diisi jika memilih pengiriman via email.',
            'target_email.email' => 'Format email target tidak valid.',
            'target_label.max' => 'Label target maksimal 100 karakter.',
            'visibility.required' => 'Pilih visibilitas surat.',
        ]);

        // 3. Validate music URL allowlist
        if (!empty($validated['music_url']) && !$this->musicValidator->isValid($validated['music_url'])) {
            return back()
                ->withInput()
                ->withErrors(['music_url' => 'URL musik harus menggunakan HTTPS dan berasal dari layanan yang diizinkan (Spotify, YouTube, Apple Music, SoundCloud).']);
        }

        // 4. Rule-based moderation check
        $modCheck = $this->moderationService->inspectText($validated['body']);
        if (!$modCheck['passed']) {
            return back()->withInput()->withErrors(['body' => $modCheck['reason']]);
        }

        $moderationStatus = 'approved';
        $visibility = $validated['visibility'];

        if ($modCheck['flagged']) {
            $moderationStatus = 'under_review';
        } elseif ($visibility === 'public_pending') {
            $moderationStatus = 'pending';
        }

        // 5. Target handling & Blocklist check
        $targetEmail = $validated['target_email'] ?? null;
        $targetHmac = $this->privacyService->computeContactHmac($targetEmail);

        if ($targetHmac) {
            $isBlocked = BlockedContact::where('contact_hmac', $targetHmac)->exists();
            if ($isBlocked) {
                // Per PRD: Do not disclose blocklist status, but prevent delivery
                $targetEmail = null;
            }
        }

        // 6. Generate secret token
        $rawToken = $this->secretLinkService->generateToken();
        $tokenHash = $this->secretLinkService->hashToken($rawToken);

        $targetLabel = $targetEmail
            ? $this->privacyService->maskEmail($targetEmail)
            : (!empty($validated['target_label']) ? trim($validated['target_label']) : null);

        // 7. Store letter
        $letter = DB::transaction(function () use ($user, $validated, $tokenHash, $targetHmac, $targetEmail, $targetLabel, $moderationStatus, $visibility) {
            $letter = Letter::create([
                'sender_user_id' => $user->id,
                'target_contact_hmac' => $targetHmac,
                'target_email_encrypted' => $targetEmail ? $this->privacyService->encryptContact($targetEmail) : null,
                'target_label' => $targetLabel,
                'body' => trim($validated['body']),
                'music_url' => !empty($validated['music_url']) ? trim($validated['music_url']) : null,
                'visibility' => $visibility,
                'moderation_status' => $moderationStatus,
                'token_hash' => $tokenHash,
                'view_count' => 0,
                'expires_at' => Carbon::now()->addDays(30),
            ]);

            return $letter;
        });

        $secretUrl = route('letters.access', ['token' => $rawToken]);

        // 8. Queue email notification if email target provided (FR-08, FR-23)
        if ($validated['target_type'] === 'email' && $targetEmail) {
            // FR-19: maksimum 3 notifikasi ke target yang sama per 24 jam.
            // Dilewati diam-diam agar pengirim tidak bisa menguji status target.
            $sentToTarget24h = DeliveryJob::where('destination_hmac', $targetHmac)
                ->where('created_at', '>=', Carbon::now()->subHours(24))
                ->count();

            if ($sentToTarget24h < 3) {
                $delivery = DeliveryJob::create([
                    'letter_id' => $letter->id,
                    'channel' => 'email',
                    'destination_hmac' => $targetHmac,
                    'destination_encrypted' => $this->privacyService->encryptContact($targetEmail),
                    'status' => 'queued',
                    'idempotency_key' => 'letter_' . $letter->id . '_email',
                    'purge_at' => Carbon::now()->addDays(7),
                ]);

                SendLetterNotificationJob::dispatch($delivery->id, $secretUrl);
            }
        }

        $emailQueued = ($validated['target_type'] === 'email' && $targetEmail);

        return redirect()->route('letters.show', $letter->id)
            ->with('raw_token', $rawToken)
            ->with('secret_url', $secretUrl)
            ->with('email_target', $emailQueued ? $letter->target_label : null)
            ->with('success', $emailQueued
                ? "Surat berhasil dibuat! Notifikasi email untuk {$letter->target_label} sudah masuk antrean pengiriman."
                : 'Surat berhasil dibuat! Simpan atau bagikan tautan rahasia ini.');
    }

    public function show(Letter $letter)
    {
        if ($letter->sender_user_id !== Auth::id()) {
            abort(403);
        }

        $mediaInfo = $this->musicValidator->getMediaInfo($letter->music_url);
        $thread = $letter->thread()->with('visibleReplies')->first();
        $delivery = $letter->latestDeliveryJob;

        return view('letters.show', compact('letter', 'mediaInfo', 'thread', 'delivery'));
    }

    public function revoke(Letter $letter)
    {
        if ($letter->sender_user_id !== Auth::id()) {
            abort(403);
        }

        $this->secretLinkService->revoke($letter);

        return back()->with('success', 'Tautan rahasia berhasil dicabut. Surat tidak lagi dapat diakses oleh pemegang link.');
    }

    public function requestPublic(Letter $letter)
    {
        if ($letter->sender_user_id !== Auth::id()) {
            abort(403);
        }

        if ($letter->revoked_at !== null || ($letter->expires_at && $letter->expires_at->isPast())) {
            return back()->with('error', 'Surat yang telah dicabut atau kedaluwarsa tidak dapat diajukan ke feed publik.');
        }

        if (in_array($letter->visibility, ['public', 'public_pending'], true)) {
            return back()->with('info', 'Surat ini sudah diajukan atau sudah tampil di feed publik.');
        }

        if ($letter->moderation_status === 'takedown') {
            return back()->with('error', 'Surat yang telah ditakedown tidak dapat diajukan kembali ke feed publik.');
        }

        $letter->update([
            'visibility' => 'public_pending',
            'moderation_status' => 'pending',
        ]);

        return back()->with('success', 'Surat berhasil diajukan untuk ditinjau oleh moderator sebelum tampil di feed publik.');
    }
}
