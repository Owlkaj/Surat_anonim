<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\Reply;
use App\Models\Report;
use App\Services\ModerationService;
use App\Services\PrivacyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function __construct(
        protected ModerationService $moderationService,
        protected PrivacyService $privacyService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content_type' => ['required', 'in:letter,reply'],
            'content_id' => ['required', 'integer'],
            'category' => ['required', 'in:threat,harassment,doxxing,defamation,spam,fraud,unwanted_sexual,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        // Pastikan konten yang dilaporkan benar-benar ada sebelum menyimpan laporan.
        if ($validated['content_type'] === 'letter') {
            if (!Letter::whereKey($validated['content_id'])->exists()) {
                return back()->with('info', 'Konten tidak tersedia untuk dilaporkan.');
            }
        } elseif (!Reply::whereKey($validated['content_id'])->exists()) {
            return back()->with('info', 'Konten tidak tersedia untuk dilaporkan.');
        }

        $ip = $request->ip();
        $reporterHmac = $this->privacyService->computeContactHmac($ip);

        // Prevent rapid flood from the same IP/account for same content
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
            return back()->with('info', 'Laporanmu sudah diterima sebelumnya dan sedang dalam peninjauan.');
        }

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

        // Evaluate auto-hide threshold
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

        return back()->with('success', 'Terima kasih atas laporanmu. Konten akan ditinjau oleh tim moderator kami.');
    }
}
