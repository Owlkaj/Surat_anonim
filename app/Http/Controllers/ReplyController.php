<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\ReplyThread;
use App\Services\AnonymousReplyService;
use App\Services\ModerationService;
use App\Services\SecretLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReplyController extends Controller
{
    public function __construct(
        protected AnonymousReplyService $replyService,
        protected ModerationService $moderationService
    ) {}

    /**
     * User's inbox of reply threads.
     */
    public function index()
    {
        $user = Auth::user();

        // Threads where user is either sender of the letter or the recipient
        $threads = ReplyThread::where(function ($q) use ($user) {
            $q->where('recipient_user_id', $user->id)
              ->orWhereHas('letter', function ($sub) use ($user) {
                  $sub->where('sender_user_id', $user->id);
              });
        })
        ->with(['letter', 'visibleReplies'])
        ->withCount('visibleReplies as replies_count')
        ->orderBy('updated_at', 'desc')
        ->paginate(15);

        return view('replies.index', compact('threads'));
    }

    /**
     * Start replying to a letter (called from read letter page after login).
     */
    public function initiate(Request $request, Letter $letter)
    {
        $user = Auth::user();

        // Check if user is the sender of the letter (sender cannot initiate recipient reply to self)
        if ($letter->sender_user_id === $user->id) {
            $thread = $letter->thread;
            if ($thread) {
                return redirect()->route('replies.show', $thread->id);
            }
            return redirect()->route('letters.show', $letter->id);
        }

        if (!$letter->isAccessible()) {
            return response()->view('letters.unavailable', [], 404);
        }

        $thread = $letter->thread;

        // Satu surat hanya memiliki satu penerima utama (PRD FR-25).
        if ($thread && $thread->recipient_user_id !== null && $thread->recipient_user_id !== $user->id) {
            abort(403, 'Surat ini sudah memiliki percakapan aktif dengan penerima lain.');
        }

        // FR-26: akses balasan hanya boleh dibuka setelah link rahasia valid dibuka
        // pada sesi ini, atau jika pengguna memang penerima thread yang sudah ada.
        $sessionToken = session('active_letter_token');
        $sessionLetterId = session('active_letter_id');
        $hasValidatedToken = is_string($sessionToken)
            && (string) $sessionLetterId === (string) $letter->id
            && hash_equals($letter->token_hash, SecretLinkService::hashToken($sessionToken));

        if (!$hasValidatedToken && $thread?->recipient_user_id !== $user->id) {
            // Respons netral agar tidak membantu enumerasi surat.
            return response()->view('letters.unavailable', [], 404);
        }

        $thread = $this->replyService->getOrCreateThread($letter, $user);

        return redirect()->route('replies.show', $thread->id);
    }

    /**
     * Show anonymous conversation thread.
     */
    public function show(ReplyThread $thread)
    {
        $user = Auth::user();

        if (!$this->replyService->isAuthorized($thread, $user)) {
            abort(403, 'Kamu tidak memiliki akses ke percakapan ini.');
        }

        $thread->load(['letter', 'visibleReplies.author']);

        $isSender = $thread->letter->sender_user_id === $user->id;
        $hiddenRepliesCount = $thread->replies()
            ->where('moderation_status', '!=', 'approved')
            ->count();

        return view('replies.show', compact('thread', 'isSender', 'hiddenRepliesCount'));
    }

    /**
     * Post a reply to the thread.
     */
    public function store(Request $request, ReplyThread $thread)
    {
        $user = Auth::user();

        if (!$this->replyService->isAuthorized($thread, $user)) {
            abort(403);
        }

        if ($thread->status !== 'active') {
            return back()->with('error', 'Percakapan ini sudah ditutup dan tidak dapat menerima pesan baru.');
        }

        if ($thread->expires_at !== null && $thread->expires_at->isPast()) {
            return back()->with('error', 'Percakapan ini sudah kedaluwarsa.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ], [
            'body.required' => 'Pesan balasan wajib diisi.',
            'body.max' => 'Pesan balasan maksimal 5.000 karakter.',
        ]);

        $modCheck = $this->moderationService->inspectText($validated['body']);
        if (!$modCheck['passed']) {
            return back()->withInput()->withErrors(['body' => $modCheck['reason']]);
        }

        $moderationStatus = $modCheck['flagged'] ? 'under_review' : 'approved';

        $reply = $this->replyService->addReply(
            $thread,
            $user,
            $validated['body'],
            $moderationStatus
        );
        $thread->touch(); // Update thread updated_at timestamp

        if ($moderationStatus === 'under_review') {
            return back()->with('info', 'Balasan terkirim, tetapi ditahan sementara untuk ditinjau moderator.');
        }

        return back()->with('success', 'Balasan anonim berhasil dikirim!');
    }
}
