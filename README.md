<div align="center">

# ✉️ SuratAnonim

**Sistem Surat Anonim Berbasis Persetujuan (*Consent-Based Anonymous Letters*)**

Kirim pesan personal tanpa menampilkan identitas, dengan kontrol penuh di tangan penerima.

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-3-38B2AC?logo=tailwind-css&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-6-646CFF?logo=vite&logoColor=white)
![Tests](https://img.shields.io/badge/tests-50%20passed-success)
![License](https://img.shields.io/badge/license-MIT-blue)

</div>

---

## 📖 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Arsitektur & Struktur Folder](#-arsitektur--struktur-folder)
- [Instalasi](#-instalasi)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [Perintah Artisan Khusus](#-perintah-artisan-khusus)
- [Pengujian](#-pengujian)
- [Keamanan & Privasi](#-keamanan--privasi)
- [Daftar Rute Utama](#-daftar-rute-utama)
- [Lisensi](#-lisensi)

---

## 💡 Tentang Proyek

**SuratAnonim** adalah aplikasi web untuk menulis surat yang identitas pengirimnya **tidak ditampilkan** kepada penerima. Berbeda dengan platform pesan anonim pada umumnya, SuratAnonim dirancang dengan prinsip:

- **Consent-first** — penerima bebas membaca, mengabaikan, membalas, melaporkan, atau memblokir.
- **Anonim bagi lawan bicara, akuntabel bagi platform** — identitas disembunyikan antarpengguna, namun platform tetap dapat menindak penyalahgunaan.
- **Privat sebagai default** — surat tidak pernah dipublikasikan tanpa pilihan eksplisit pengirim **dan** persetujuan moderator.

### Peran Pengguna

| Peran | Hak Akses |
|---|---|
| **Pengunjung** | Melihat feed publik dan melaporkan konten |
| **Pengirim** (login) | Membuat, mengelola, mencabut tautan, dan mengajukan publikasi surat |
| **Penerima** (tanpa akun) | Membaca surat melalui tautan rahasia, melapor, dan memblokir |
| **Penerima** (login) | Membalas surat secara anonim melalui thread |
| **Admin / Moderator** | Meninjau publikasi, menangani laporan, dan melakukan takedown |

---

## ✨ Fitur Utama

| Fitur | Deskripsi |
|---|---|
| 📝 **Buat Surat** | Teks 1–5.000 karakter, target email / label manual, dan visibilitas privat atau pengajuan publik |
| 🔗 **Tautan Rahasia** | Token acak 21 karakter, berlaku 30 hari, dapat dicabut, dan dapat dihapus permanen |
| 📧 **Notifikasi Email** | Hanya berisi tautan, **tanpa isi surat**; diproses lewat queue dengan retry & idempotensi |
| 🎵 **Musik Latar** | Autoplay tanpa kotak pemutar: Spotify, YouTube, YouTube Music, Apple Music, SoundCloud + tombol volume mengambang |
| 💬 **Balasan Anonim** | Thread dua arah dengan label netral "Pengirim anonim" dan "Penerima" |
| 🌐 **Feed Publik** | Hanya menampilkan surat yang diajukan pengirim dan disetujui moderator |
| 🚩 **Laporan & Auto-Hide** | Surat otomatis ditahan setelah mencapai ambang laporan |
| 🚫 **Blokir Kontak** | Penerima dapat berhenti menerima email dari platform |
| 🛡️ **Panel Moderasi** | Setujui, tolak, takedown, dan selesaikan laporan, lengkap dengan audit log |
| 🗂️ **Dashboard Hybrid** | Kartu surat yang dapat disusun ulang (drag-and-drop & keyboard) |
| 🧹 **Retensi Data** | Pembersihan otomatis isi surat, balasan, dan data kontak yang kedaluwarsa |

---

## 🛠 Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | PHP 8.2+, Laravel 12, Eloquent ORM |
| Autentikasi | Laravel Breeze (Blade) |
| Frontend | Blade, Tailwind CSS, Vanilla JavaScript, font Inter |
| Build Tool | Vite, PostCSS |
| Database | SQLite (default) — kompatibel dengan MySQL |
| Queue | Laravel Database Queue |
| Testing | PHPUnit 11 |

---

## 🏗 Arsitektur & Struktur Folder

Aplikasi menggunakan pola **MVC + Service Layer**: controller tetap tipis, sedangkan logika bisnis ditempatkan di `app/Services`.

```
app/
├── Console/Commands/     # Perintah artisan khusus (admin, purge, test email)
├── Http/Controllers/     # Letter, LetterAccess, Reply, Report, PublicFeed, Admin/Moderation
├── Jobs/                 # SendLetterNotificationJob (pengiriman email via queue)
├── Mail/                 # LetterNotificationMail
├── Models/               # Letter, ReplyThread, Reply, Report, BlockedContact, dll.
└── Services/
    ├── SecretLinkService.php      # Pembuatan & hashing token
    ├── PrivacyService.php         # HMAC kontak, enkripsi, masking email
    ├── ModerationService.php      # Filter konten & ambang laporan
    ├── MusicUrlValidator.php      # Allowlist URL musik
    ├── AnonymousReplyService.php  # Thread & otorisasi balasan
    └── RetentionService.php       # Pembersihan data kedaluwarsa
database/migrations/      # Skema 10+ tabel
resources/views/          # Blade views (letters, feed, replies, admin, auth)
routes/web.php            # Definisi rute aplikasi
tests/                    # Unit & Feature tests
```

### Skema Database

| Tabel | Fungsi |
|---|---|
| `users` | Akun pengguna |
| `letters` | Surat, token hash, visibilitas, status moderasi, masa berlaku |
| `reply_threads` | Thread percakapan per surat |
| `replies` | Pesan balasan dalam thread |
| `delivery_jobs` | Status pengiriman email (queued → sent / failed) |
| `reports` | Laporan pelanggaran konten |
| `blocked_contacts` | Daftar kontak yang diblokir (disimpan sebagai HMAC) |
| `admin_users` | Peran admin / moderator |
| `audit_logs` | Jejak tindakan moderasi |
| `rate_limits` | Pembatasan frekuensi aksi |

---

## 🚀 Instalasi

### Prasyarat

- PHP **8.2** atau lebih baru (ekstensi `pdo_sqlite`, `mbstring`, `openssl`)
- Composer
- Node.js **18+** & npm

### Langkah Instalasi

```bash
# 1. Clone repository
git clone https://github.com/Owlkaj/Surat_anonim.git
cd Surat_anonim

# 2. Install dependensi
composer install
npm install

# 3. Siapkan file environment
cp .env.example .env
php artisan key:generate

# 4. Siapkan database (SQLite)
touch database/database.sqlite      # Windows: type nul > database\database.sqlite
php artisan migrate

# 5. Build aset frontend
npm run build
```

> **Alternatif cepat:** `composer setup` menjalankan langkah 2–5 secara otomatis.

### Konfigurasi Email (opsional)

Secara default `MAIL_MAILER=log`, sehingga email ditulis ke `storage/logs/laravel.log`. Untuk mengirim email sungguhan, atur SMTP di `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="SuratAnonim"
MAIL_REPLY_TO_ADDRESS="hello@example.com"
MAIL_REPLY_TO_NAME="SuratAnonim"
MAIL_UNSUBSCRIBE_URL="https://example.com/berhenti-email"
PUBLIC_APP_URL="https://example.com"
```

> **Agar tidak masuk folder spam:** gunakan domain sendiri (bukan `@gmail.com` atau tunnel sementara), aktifkan **SPF**, **DKIM**, dan **DMARC** di DNS, lalu kirim melalui penyedia email transaksional. Isi `PUBLIC_APP_URL` dengan domain https publik agar tautan surat di email dapat dibuka penerima. Jalankan `php artisan surat:test-email kamu@email.com` untuk melihat checklist deliverability.

---

## ▶️ Menjalankan Aplikasi

**Mode pengembangan** (server, queue worker, log, dan Vite sekaligus):

```bash
composer dev
```

**Atau jalankan secara manual** di terminal terpisah:

```bash
php artisan serve          # Web server → http://127.0.0.1:8000
php artisan queue:work     # Worker pengiriman email
npm run dev                # Hot reload aset frontend
```

---

## ⚙️ Perintah Artisan Khusus

| Perintah | Fungsi |
|---|---|
| `php artisan surat:make-admin {email} --role=admin` | Menjadikan pengguna sebagai admin / moderator |
| `php artisan letters:purge-expired` | Menjalankan pembersihan data kedaluwarsa |
| `php artisan surat:test-email {to}` | Menguji konfigurasi pengiriman email |

Contoh membuat moderator:

```bash
php artisan surat:make-admin moderator@example.com --role=moderator
```

---

## 🧪 Pengujian

```bash
php artisan test
```

Hasil saat ini: **50 tests passed (154 assertions)**, mencakup:

- Unit test layanan (token, validasi musik, masking email, moderasi)
- Autentikasi & profil
- Pengiriman email (idempotensi, retry, bounce permanen, batas harian)
- Akses tautan rahasia, thread balasan, ambang laporan, blokir, moderasi feed, dan retensi data

---

## 🔒 Keamanan & Privasi

| Aspek | Implementasi |
|---|---|
| Token tautan | CSPRNG 21 karakter; database hanya menyimpan **hash SHA-256** |
| Kontak target | Dicocokkan menggunakan **HMAC-SHA256**; email disimpan terenkripsi sementara |
| Tampilan email | Disamarkan (`a***@domain.com`) |
| Isi email notifikasi | Tidak memuat isi surat maupun link musik |
| XSS | Seluruh konten pengguna dirender sebagai teks biasa |
| Otorisasi | Pemeriksaan server-side untuk thread, surat, dan panel admin (anti-IDOR) |
| Redirect login | Hanya menerima path lokal (anti open-redirect) |
| Rate limiting | Pembuatan surat, notifikasi per target, akses tautan, dan laporan |
| Retensi | Isi surat, balasan, dan kontak terenkripsi dihapus setelah kedaluwarsa |

> ⚠️ **Catatan:** "Anonim" berarti anonim terhadap lawan bicara dan publik, **bukan** anonimitas absolut terhadap operator platform atau proses hukum yang sah.

---

## 🗺 Daftar Rute Utama

| Metode | URL | Keterangan | Akses |
|---|---|---|---|
| GET | `/` | Halaman utama | Publik |
| GET | `/feed` | Feed publik termoderasi | Publik |
| GET | `/letters/{token}` · `/l/{token}` · `/buka/{token}` | Membaca surat via tautan rahasia | Publik |
| POST | `/reports` | Mengirim laporan | Publik |
| GET | `/dashboard` | Dashboard pengguna | Login |
| GET / POST | `/letters`, `/letters/create` | Daftar & buat surat | Login |
| POST | `/my-letters/{id}/revoke` | Cabut tautan | Login |
| POST | `/my-letters/{id}/request-public` | Ajukan ke feed publik | Login |
| GET | `/inbox`, `/threads/{id}` | Kotak masuk & thread balasan | Login |
| GET | `/admin/moderation` | Panel moderasi | Admin / Moderator |

---

## 📄 Lisensi

Proyek ini dirilis di bawah lisensi [MIT](https://opensource.org/licenses/MIT).

<div align="center">

Dibuat untuk Tugas Besar **Pemrograman Berbasis Kerangka Kerja (PBKK)**.

</div>
