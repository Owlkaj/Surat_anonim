<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Letter;
use App\Models\Reply;
use App\Models\ReplyThread;
use App\Models\Report;
use App\Services\AnonymousReplyService;
use App\Services\ModerationService;
use App\Services\PrivacyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ReportController extends Controller
{
    public function __construct(
        protected ModerationService $moderationService,
        protected PrivacyService $privacyService,
        protected AnonymousReplyService $replyService
    ) {}

    /**
     * Tampilkan/membuat laporan.
     *
     * Endpoint ini menerima laporan dari tamu maupun pengguna terdaftar. Laporan
     * tamu TIDAK langsung memicu penyembunyian konten; hanya pengguna terverifikasi
     * yang dapat memicu ambang batas moderasi. Ini mencegah laporan anonim
     * digunakan untuk memblokir/menyembunyikan konten siapa pun (H-02).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content_type' => ['required', 'in:letter,reply'],
            'content_id' => ['required', 'integer', 'min:1'],
            'category' => ['required', 'in:threat,harassment,doxxing,defamation,spam,fraud,unwanted_sexual,other'],
            'details' => ['nullable', 'string', 'max:1000'],
            // Tanda tangan bahwa pelapor memiliki akses ke konten yang dilaporkan
            // (tautan rahasia surat atau keanggotaan thread). Tidak pernah bocorkan
            // apakah konten ada.
            'access_token' => ['nullable', 'string', 'max:128'],
        ]);

        // 1. Validasi akses pelapor ke konten. Jika tidak punya akses, terima laporan
        //    secara generik TANPA mencatat (atau catat tanpa detail) agar tidak ada
        //    oracle keberadaan konten.
        if (!$this->canReport($request, $validated['content_type'], $validated['content_id'])) {
            return Redirect::back()
                ->with('info', 'Terima kasih, laporanmu sudah kami terima.');
        }

        // 2. Hindari duplikasi laporan untuk konten yang sama (per pelapor/6 jam).
        $ip = $request->ip();
        $reporterHmac = $this->privacyService->computeContactHmac($ip);

        $existing = Report::where('content_type', $validated['content_type'])
            ->where('content_id', $validated['content_id'])
            ->where(function ($q) use ($reporterHmac) {
                if (Auth::check()) {
                    $q->where('reporter_user_id', Auth::id());
                } else {
                    $q->where('reporter_hmac', $reporterHmac);
                }
            })
            ->where('created_at', '>=', now()->subHours(6))
            ->first();

        if ($existing) {
            return Redirect::back()
                ->with('info', 'Laporanmu sudah diterima sebelumnya dan sedang dalam peninjauan.');
        }

        // 3. Simpan laporan. Pengguna tamu tetap tercatat untuk tinjauan moderator,
        //    tetapi tidak boleh menghitung ambang batas penyembunyian (lihat
        //    ModerationService).
        $report = Report::create([
            'content_type' => $validated['content_type'],
            'content_id' => $validated['content_id'],
            'reporter_user_id' => Auth::id(),
            'reporter_hmac' => $reporterHmac,
            'category' => $validated['category'],
            'details' => $validated['details'] ? trim($validated['details']) : null,
            'status' => 'pending',
            'purge_at' => now()->addDays(180),
        ]);

        // 4. Evaluasi ambang batas auto-sembunyi: hitung HANYA laporan dari pengguna
        //    terdaftar (bukan tamu/spoofed IP), minimal 3 pelapor berbeda dalam 24 jam.
        if (Auth::check()) {
            if ($validated['content_type'] === 'letter') {
                $letter = Letter::find($validated['content_id']);
                if ($letter) {
                    $this->moderationService->checkReportThreshold($letter);
                }
            } elseif ($validated['content_type'] === 'reply') {
                $reply = Reply::find($validated['content_id']);
                if ($reply) {
                    $this->moderationService->checkReplyReportThreshold($reply);
                }
            }
        }

        return Redirect::back()
            ->with('success', 'Terima kasih atas laporanmu. Konten akan ditinjau oleh tim moderator kami.');
    }

    /**
     * Cek apakah pelapor (auth atau tamu) berwenang melaporkan konten ini.
     */
    protected function canReport(Request $request, string $contentType, int $contentId): bool
    {
        if ($contentType === 'letter') {
            $letter = Letter::find($contentId);

            // Jika konten tidak ada, jangan bocorkan (tidak mengembalikan pesan
            // "tidak tersedia" dan tidak menyimpan laporan).
            if (!$letter) {
                return false;
            }

            // Pengguna terautentikasi (memiliki akun terdaftar) boleh melaporkan
            // surat yang ada. Identitas mereka dicatat untuk akuntabilitas.
            if (Auth::check()) {
                return true;
            }

            // Pengguna tamu (belum login) HANYA boleh melaporkan jika surat publik
            // atau jika mereka menyertakan access_token yang valid (membuktikan mereka
            // penerima yang sah membuka tautan rahasia).
            if ($letter->isPublicActive()) {
                return true;
            }

            $token = (string) $request->input('access_token');
            if ($token !== '' && hash_equals(
                $letter->token_hash,
                \App\Services\SecretLinkService::hashToken($token)
            )) {
                return true;
            }

            return false;
        }

        // Balasan: hanya anggota thread yang bisa melaporkan.
        $reply = Reply::find($contentId);
        if (!$reply) {
            return false;
        }

        if (!Auth::check()) {
            return false;
        }

        return $this->replyService->isAuthorized($reply->thread, Auth::user());
    }

    /**
     * Apakah pengguna merupakan anggota thread surat (pengirim atau penerima).
     */
    protected function isReplyParticipant(Letter $letter, $user): bool
    {
        return $letter->thread?->recipient_user_id === $user->id
            || $letter->sender_user_id === $user->id;
    }
}
