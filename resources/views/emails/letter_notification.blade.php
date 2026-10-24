<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Surat Anonim</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F7FAFC; color: #172B3A; margin: 0; padding: 30px 15px; }
        .card { max-width: 520px; margin: 0 auto; background: #FFFFFF; border-radius: 16px; border: 1px solid #E2EAF0; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .brand { font-size: 20px; font-weight: 700; color: #38A9F5; margin-bottom: 24px; }
        h1 { font-size: 18px; color: #172B3A; margin-top: 0; line-height: 1.4; }
        p { font-size: 14px; line-height: 1.6; color: #617586; margin-bottom: 20px; }
        .btn { display: inline-block; background-color: #38A9F5; color: #FFFFFF !important; text-decoration: none; padding: 12px 28px; font-size: 14px; font-weight: 600; border-radius: 10px; margin: 10px 0 24px 0; }
        .footer { font-size: 12px; color: #8F9CA7; border-top: 1px solid #E2EAF0; padding-top: 20px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">SuratAnonim</div>
        <h1>Ada seseorang yang mengirimkan surat personal kepadamu</h1>
        <p>Seseorang telah menulis surat dan membagikannya secara privat kepadamu melalui tautan rahasia. Identitas pengirim dirahasiakan oleh sistem kami.</p>
        <p>Sesuai komitmen privasi SuratAnonim, isi surat tidak dimuat di dalam email ini. Kamu dapat membacanya secara aman melalui tautan berikut:</p>
        <div style="text-align: center;">
            <a href="{{ $secretUrl }}" class="btn" target="_blank" rel="noopener noreferrer">Buka Surat Anonim</a>
        </div>
        <p style="font-size: 13px;">Jika tombol di atas tidak dapat diklik, salin dan tempel tautan berikut di browsermu:<br><a href="{{ $secretUrl }}" style="color: #38A9F5; word-break: break-all;">{{ $secretUrl }}</a></p>
        <div class="footer">
            <p style="margin: 0 0 8px 0;"><strong>Catatan Privasi:</strong> Kamu dapat membaca surat tanpa membuat akun. Jika kamu tidak mengenal pengirim atau tidak ingin membacanya, kamu dapat mengabaikan email ini.</p>
            <p style="margin: 0;">&copy; {{ date('Y') }} SuratAnonim. Seluruh hak cipta dilindungi.</p>
        </div>
    </div>
</body>
</html>
