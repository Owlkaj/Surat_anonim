<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Services\MusicUrlValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PublicFeedController extends Controller
{
    public function __construct(
        protected MusicUrlValidator $musicValidator
    ) {}

    public function index()
    {
        $letters = Letter::where('visibility', 'public')
            ->where('moderation_status', 'approved')
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return view('feed.index', compact('letters'));
    }

    public function show(Letter $letter)
    {
        if (!$letter->isPublicActive()) {
            return response()->view('letters.unavailable', [], 404);
        }

        // Hitung pembacaan feed dengan deduplikasi per jendela waktu.
        $viewSessionKey = 'viewed_public_letter_'.$letter->id;
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

        return view('feed.show', compact('letter', 'mediaInfo'));
    }
}
