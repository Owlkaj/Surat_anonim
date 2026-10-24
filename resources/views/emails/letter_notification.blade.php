<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Pemberitahuan Surat Baru</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F7FAFC; color: #172B3A; margin: 0; padding: 30px 15px; }
        .preheader { display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; overflow: hidden; }
        .card { max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 16px; border: 1px solid #E2EAF0; padding: 32px; }
        .brand { font-size: 20px; font-weight: 700; color: #126FB3; margin-bottom: 8px; }
        .tagline { font-size: 12px; color: #617586; margin: 0 0 24px 0; }
        h1 { font-size: 18px; color: #172B3A; margin-top: 0; line-height: 1.45; }
        p { font-size: 14px; line-height: 1.6; color: #617586; margin-bottom: 18px; }
        .btn { display: inline-block; background-color: #168DE0; color: #FFFFFF !important; text-decoration: none; padding: 12px 28px; font-size: 14px; font-weight: 600; border-radius: 10px; margin: 6px 0 24px 0; }
        .link { color: #126FB3; word-break: break-all; }
        .footer { font-size: 12px; color: #617586; border-top: 1px solid #E2EAF0; padding-top: 18px; line-height: 1.6; }
        .footer p { font-size: 12px; color: #617586; margin: 0 0 8px 0; }
    </style>
</head>
<body>
    <span class="preheader">Ada surat baru untukmu. Buka tautan aman berikut untuk membacanya tanpa membuat akun.</span>

    <div class="card">
        <div class="brand">{{ config('app.name', 'SuratAnonim') }}</div>
        <p class="tagline">Surat personal dengan identitas pengirim yang dirahasiakan.</p>

        <h1>Ada surat baru untukmu</h1>
        <p>Seseorang telah menulis surat dan membagikannya secara privat kepadamu melalui {{ config('app.name', 'SuratAnonim') }}. Identitas pengirim tidak ditampilkan oleh sistem kami.</p>
        <p>Sesuai komitmen privasi kami, isi surat tidak dimuat di dalam email ini. Kamu dapat membacanya dengan aman melalui tautan berikut:</p>

        <div style="text-align: center;">
            <a href="{{ $secretUrl }}" class="btn" target="_blank" rel="noopener noreferrer">Buka Surat</a>
        </div>

        <p style="font-size: 13px;">
            Jika tombol di atas tidak dapat diklik, salin dan tempel tautan berikut di browsermu:<br>
            <a href="{{ $secretUrl }}" class="link">{{ $secretUrl }}</a>
        </p>

        <div class="footer">
            <p><strong>Mengapa kamu menerima email ini?</strong> Email ini dikirim otomatis karena seseorang memasukkan alamat ini sebagai penerima surat. Kamu tidak perlu membalas email ini.</p>
            <p>Kamu dapat membaca surat tanpa membuat akun. Jika kamu tidak mengenal pengirim atau tidak ingin membacanya, abaikan saja email ini.</p>
            <p>
                Bantuan: <a href="mailto:{{ config('mail.reply_to.address') }}" class="link">{{ config('mail.reply_to.address') }}</a>
                @if (config('mail.unsubscribe_url'))
                    &bull; <a href="{{ config('mail.unsubscribe_url') }}" class="link">Berhenti menerima email</a>
                @endif
            </p>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'SuratAnonim') }}. Seluruh hak cipta dilindungi.</p>
        </div>
    </div>
</body>
</html>
