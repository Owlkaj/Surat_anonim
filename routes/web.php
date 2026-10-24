<?php

use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\LetterAccessController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicFeedController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\ReportController;
use App\Models\Letter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public Feed
Route::get('/feed', [PublicFeedController::class, 'index'])->name('feed.index');
Route::get('/feed/{letter}', [PublicFeedController::class, 'show'])->name('feed.show');

// Public Letter Secret Link Access
Route::get('/letters/{token}', [LetterAccessController::class, 'show'])
    ->middleware('throttle:letter-access')
    ->name('letters.access')
    ->where('token', '[A-Za-z0-9_-]{15,}');
Route::post('/letters/{token}/block', [LetterAccessController::class, 'block'])
    ->middleware('throttle:letter-block')
    ->name('letters.block')
    ->where('token', '[A-Za-z0-9_-]{15,}');
Route::get('/buka/{token}', [LetterAccessController::class, 'show'])
    ->middleware('throttle:letter-access')
    ->name('letters.buka');
Route::post('/buka/{token}/block', [LetterAccessController::class, 'block'])
    ->middleware('throttle:letter-block')
    ->name('letters.buka.block');
Route::get('/l/{token}', [LetterAccessController::class, 'show'])
    ->middleware('throttle:letter-access')
    ->name('letters.short');

// Public / Authenticated Reporting
Route::post('/reports', [ReportController::class, 'store'])
    ->middleware('throttle:reports')
    ->name('reports.store');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard with Hybrid Canvas "Surat-suratmu"
    Route::get('/dashboard', function () {
        $user = Auth::user();

        $totalLetters = Letter::where('sender_user_id', $user->id)->count();
        $openedLetters = Letter::where('sender_user_id', $user->id)->where('view_count', '>', 0)->count();
        $totalReplies = DB::table('replies')
            ->join('reply_threads', 'replies.thread_id', '=', 'reply_threads.id')
            ->join('letters', 'reply_threads.letter_id', '=', 'letters.id')
            ->where('letters.sender_user_id', $user->id)
            ->where('replies.author_user_id', '!=', $user->id)
            ->where('replies.moderation_status', 'approved')
            ->whereNull('replies.deleted_at')
            ->count();

        // Latest letters for dashboard canvas
        $canvasLetters = Letter::where('sender_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        return view('dashboard', compact('totalLetters', 'openedLetters', 'totalReplies', 'canvasLetters'));
    })->name('dashboard');

    // Letters Management
    Route::get('/letters', [LetterController::class, 'index'])->name('letters.index');
    Route::get('/letters/create', [LetterController::class, 'create'])->name('letters.create');
    Route::post('/letters', [LetterController::class, 'store'])->name('letters.store');
    Route::get('/my-letters/{letter}', [LetterController::class, 'show'])->name('letters.show');
    Route::delete('/my-letters/{letter}', [LetterController::class, 'destroy'])->name('letters.destroy');
    Route::post('/my-letters/{letter}/revoke', [LetterController::class, 'revoke'])->name('letters.revoke');
    Route::post('/my-letters/{letter}/request-public', [LetterController::class, 'requestPublic'])->name('letters.requestPublic');

    // Reply Threads & Inbox
    Route::get('/inbox', [ReplyController::class, 'index'])->name('replies.index');
    Route::get('/letters/{letter}/reply', [ReplyController::class, 'initiate'])->name('replies.initiate');
    Route::get('/threads/{thread}', [ReplyController::class, 'show'])->name('replies.show');
    Route::post('/threads/{thread}', [ReplyController::class, 'store'])->name('replies.store');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin / Moderation Panel (middleware + pengecekan controller)
    Route::prefix('admin')->name('admin.')->middleware('can:moderate-content')->group(function () {
        Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
        Route::post('/letters/{letter}/approve', [ModerationController::class, 'approveLetter'])->name('letters.approve');
        Route::post('/letters/{letter}/reject', [ModerationController::class, 'rejectLetter'])->name('letters.reject');
        Route::post('/letters/{letter}/takedown', [ModerationController::class, 'takedownLetter'])->name('letters.takedown');
        Route::post('/reports/{report}/resolve', [ModerationController::class, 'resolveReport'])->name('reports.resolve');
    });
});

require __DIR__.'/auth.php';
