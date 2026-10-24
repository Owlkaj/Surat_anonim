<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tanggal dan waktu mengikuti bulan dalam bahasa Indonesia.
        Carbon::setLocale('id');

        // Otorisasi panel moderasi di level route, bukan hanya di controller.
        Gate::define('moderate-content', function (User $user): bool {
            return $user->isModerator();
        });

        // Pembatasan laporan: per akun untuk pengguna login, per IP untuk tamu.
        RateLimiter::for('reports', function (Request $request): Limit {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perHour(10)->by('reports:'.$key);
        });

        // Pembatasan pembukaan link rahasia untuk mengurangi enumerasi token.
        RateLimiter::for('letter-access', function (Request $request): Limit {
            return Limit::perMinute(30)->by('letter-access:'.$request->ip());
        });

        // Pembatasan aksi opt-out/blokir agar tidak dapat di-spam.
        RateLimiter::for('letter-block', function (Request $request): Limit {
            return Limit::perHour(10)->by('letter-block:'.($request->user()?->id ?: $request->ip()));
        });
    }
}
