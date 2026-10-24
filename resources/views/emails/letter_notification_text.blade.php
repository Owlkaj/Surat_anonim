{{ config('app.name', 'SuratAnonim') }}

Ada surat baru untukmu.

Seseorang telah menulis surat dan membagikannya secara privat kepadamu melalui {{ config('app.name', 'SuratAnonim') }}. Identitas pengirim tidak ditampilkan oleh sistem kami.

Sesuai komitmen privasi kami, isi surat tidak dimuat di dalam email ini. Buka surat melalui tautan berikut:

{{ $secretUrl }}

Kamu dapat membaca surat tanpa membuat akun. Jika kamu tidak mengenal pengirim atau tidak ingin membacanya, abaikan saja email ini.

--
Mengapa kamu menerima email ini?
Email ini dikirim otomatis karena seseorang memasukkan alamat ini sebagai penerima surat. Kamu tidak perlu membalas email ini.

Bantuan: {{ config('mail.reply_to.address') }}
@if (config('mail.unsubscribe_url'))
Berhenti menerima email: {{ config('mail.unsubscribe_url') }}
@endif

(c) {{ date('Y') }} {{ config('app.name', 'SuratAnonim') }}
