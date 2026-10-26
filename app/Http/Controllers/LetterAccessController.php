<?php

namespace App\Http\Controllers;

use App\Models\BlockedContact;
use App\Models\Letter;
use App\Services\AnonymousReplyService;
use App\Services\MusicUrlValidator;
use App\Services\SecretLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class LetterAccessController extends Controller
{
    public function __construct(
        protected SecretLinkService $secretLinkService,
        protected MusicUrlValidator $musicValidator,
        protected AnonymousReplyService $replyService
    ) {}

    /**
     * Baca surat anonim lewat tautan rahasia.
     */
    public function show(string $token)
    {
        $letter = $this->secretLinkService->findByRawToken($token);

        if (!$letter) {
            // Anti-enumerasi: respons netral tanpa mengungkap kenapa surat tidak tersedia.
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

        // Simpan token surat yang sedang aktif di sesi, agar setelah login/register
        // pengguna bisa membalas surat tanpa perlu membuka tautan lagi.
        Session::put('active_letter_token', $token);
        Session::put('active_letter_id', $letter->id);

        $thread = $letter->thread()->with('visibleReplies')->first();

        return view('letters.read', compact('letter', 'mediaInfo', 'token', 'thread'));
    }

    /**
     * HENTIKAN MENERIMA EMAIL (opt-out) lewat tautan bertanda tangan.
     *
     * Tautan ini hanya dikirimkan ke alamat email penerima (lihat
     * LetterNotificationMail & SendLetterNotificationJob), sehingga pemegangnya
     * telah membuktikan akses ke mailbox. Tautan ini tidak dapat digenerate oleh
     * pengirim surat karena memerlukan rahasia aplikasi (APP_KEY).
     */
    public function blockFromEmail(Request $request, Letter $letter)
    {
        // Middleware 'signed' sudah memastikan tautan ini cocok dengan surat
        // (ID surat + tanda tangan) dan belum kedaluwarsa (30 hari).

        if (!$letter->target_contact_hmac) {
            // Surat manual (tanpa email penerima) tidak memiliki email untuk diblokir.
            return Redirect::back()
                ->with('info', 'Tidak ada pengaturan pengiriman email pada surat ini.');
        }

        BlockedContact::firstOrCreate(
            ['contact_hmac' => $letter->target_contact_hmac],
            [
                'owner_user_id' => Auth::id(),
                'reason_code' => 'recipient_opt_out',
                'source' => 'recipient_email',
            ]
        );

        return Redirect::route('home')
            ->with('success', 'Terima kasih. Email terkait surat ini sudah dihentikan, dan pengiriman berikutnya terkait pesan ini akan diblokir.');
    }
}
