# Anare Clinic — Laravel Blade Website

Website klinik dengan Laravel 12, Blade, CSS, dan JavaScript vanilla. Proyek ini awalnya hanya berisi overlay frontend; bootstrap Laravel dan dependensi Composer sekarang sudah tersedia sehingga dapat dijalankan langsung dari folder ini.

## Kebutuhan lokal

- PHP 8.2+ dan Composer 2.
- Ekstensi PHP yang dibutuhkan Laravel, terutama `fileinfo`, `mbstring`, `openssl`, dan `dom`. Aktifkan `zip` agar Composer dapat mengekstrak paket tanpa alat tambahan.
- Tidak membutuhkan Node.js, npm, database, migrasi, atau queue worker untuk demo saat ini.
- Internet diperlukan untuk instalasi dependensi, Google Fonts, Google Maps, dan konten Instagram.

## Cara menjalankan sendiri di komputer ini

Buka folder `D:\project\anare-webapp` di VS Code, lalu pilih **Terminal > New Terminal**. Jalankan:

```powershell
cd D:\project\anare-webapp
composer run dev
```

Buka **http://127.0.0.1:8000** di browser. Biarkan terminal tetap berjalan selama website digunakan. Untuk menghentikan server, tekan **Ctrl+C** di terminal tersebut.

Dependensi dan `.env` di folder ini sudah disiapkan. Untuk pemakaian sehari-hari, cukup `composer run dev`; tidak perlu menjalankan setup lagi.

## Setup pertama kali

Jalankan dari folder proyek:

```powershell
composer run setup
composer run dev
```

`composer run setup` menginstal paket dari `composer.lock`, membuat `.env` dari `.env.example` jika belum ada, dan menghasilkan `APP_KEY`. Gunakan untuk setup awal; perintah tersebut menghasilkan ulang key jika dijalankan lagi.

Buka **http://127.0.0.1:8000**. Hentikan server yang berjalan di terminal dengan `Ctrl+C`.

## Menjalankan kembali

Jika dependensi dan `.env` sudah tersedia, cukup jalankan:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Perintah ini setara dengan `composer run dev`. Jika port 8000 digunakan proses lain, jalankan dengan `--port=8001` dan sesuaikan `APP_URL` di `.env`.

## Konfigurasi

`.env.example` menyertakan konfigurasi frontend tanpa database:

```dotenv
APP_URL=http://127.0.0.1:8000
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Session, cache, hasil kompilasi Blade, dan log disimpan di `storage/`. Direktori `storage/` dan `bootstrap/cache/` harus dapat ditulis oleh PHP. `.env` dan `vendor/` tidak disertakan dalam version control.

`config/app.php` mengatur nama aplikasi, URL, locale, timezone `Asia/Jakarta`, dan encryption key. Konfigurasi layanan lain menggunakan default Laravel yang membaca variabel `.env`; jika perlu override, tambahkan file konfigurasi di `config/`, bukan mengedit `vendor/`.

## Pilihan bahasa ID / EN

Tombol **ID / EN** tersedia di samping navbar, termasuk pada mobile. Pilihan berlaku untuk halaman utama, katalog/detail Skin dan Shape, dokter/modal, artikel, footer, dan label interaksi. Preferensi tersimpan dalam session serta cookie selama satu tahun, dan tetap digunakan saat berpindah halaman atau membuka sesi baru.

Pergantian bahasa mempertahankan path, query/filter, dan anchor URL. Bahasa default mengikuti `APP_LOCALE` (default `en`). Kamus dapat diedit di `lang/id/site.php` dan `lang/en/site.php`; gunakan key yang sama pada keduanya. Tulisan pada gambar jadwal asli tetap mengikuti aset, dengan ringkasan jadwal Inggris di modal saat EN aktif.

Panduan alur locale, penambahan terjemahan, dan cakupan pengujian tersedia di [docs/LOCALIZATION.md](docs/LOCALIZATION.md).

## Halaman

| URL | Fungsi |
| --- | --- |
| `/` | Home, layanan, dokter, artikel unggulan, galeri, lokasi, dan ulasan |
| `/skin` | Katalog Anare Skin, delapan kategori, enam treatment, dan pagination |
| `/shape` | Katalog layanan Shape dengan filter kategori |
| `/skin/{slug}` | Detail treatment Skin berdasarkan data dan slug, misalnya `/skin/acne-injection` |
| `/shape/{slug}` | Detail treatment Shape |
| `/doctors` | Daftar dokter |
| `/doctors/{slug}` | Profil dokter |
| `/articles` | Daftar artikel dengan filter kategori |
| `/articles/{slug}` | Detail artikel |
| `/language/{locale}` (POST) | Menyimpan pilihan bahasa `id` atau `en` |
| `/up` | Health check Laravel |

## Admin CMS demo

Buka **http://127.0.0.1:8000/admin**, lalu masuk dengan username **`admin`** dan kata sandi **`anare123`**.

Panel mengikuti referensi login putih dan CMS sidebar gelap. Menu meliputi Dashboard, Beranda, Anare Skin, Anare Shape, Dokter, Artikel, dan Pengaturan. Konten awal menggunakan data/foto website customer. Editor memiliki tab Indonesia/Inggris, pratinjau, upload gambar lokal, tambah/edit/duplikat/hapus, pencarian, filter, dan pagination.

Fitur ini berfokus pada UI: login memakai session dummy dan perubahan konten tersimpan di browser (`localStorage`), tanpa database. Perubahan belum dipublikasikan ke website customer. Detail akses, penyimpanan, kode, serta pengujian tersedia di [docs/ADMIN.md](docs/ADMIN.md).

## Lokasi kode utama

- `routes/web.php`: route frontend dan parameter view.
- `resources/views/layouts/app.blade.php`: layout bersama, navbar, footer, dan pemuatan font.
- `public/js/anare.js`: menu mobile, dropdown Services untuk touch/keyboard/mouse, dan filter kategori.
- `resources/views/pages/`: template halaman aplikasi.
- `config/skin.php`: data kategori dan treatment Anare Skin.
- `config/shape.php`: data kategori dan treatment Anare Shape.
- `config/hero.php`: tiga foto dummy lokal dan interval slideshow untuk tiap hero.
- `config/doctors.php`: nama, urutan foto, panel jadwal, dan konten modal dokter.
- `config/social.php`: dua sumber Instagram, konfigurasi API opsional, dan tautan WhatsApp.
- `lang/id/site.php`, `lang/en/site.php`: kamus konten dan label interaksi Indonesia/Inggris.
- `app/Http/Middleware/SetLocale.php`, `app/Http/Controllers/LocaleController.php`: pemilihan dan penyimpanan preferensi bahasa.
- `app/Support/LocalizedContent.php`: terjemahan data treatment dan dokter tanpa mengubah slug.
- `resources/data/instagram-feed.json`: cache dan provenance postingan Instagram yang telah diambil.
- `app/Http/Controllers/TreatmentController.php`: kategori, pagination, detail treatment, dan navigasi kembali untuk kedua layanan.
- `resources/views/pages/treatments/`: template katalog dan detail bersama untuk Skin dan Shape.
- `resources/views/partials/cards.blade.php`: kartu katalog Shape/legacy.
- `resources/views/partials/clinic-footer.blade.php`: alamat, jam buka, tiga akun Instagram, dan nomor WhatsApp bersama.
- `public/css/anare.css`: tema Skin/Shape, komponen, dan responsive styling Home.
- `resources/views/admin/`, `public/css/admin.css`, `public/js/admin.js`: tampilan dan interaksi CMS demo terpisah.
- `app/Http/Controllers/AdminController.php`, `app/Support/AdminDemoContent.php`: login/session dummy serta seed data admin dari website customer.
- `public/images/`: foto dan logo JPG/PNG asli yang disediakan pengguna.
- `bootstrap/app.php`, `bootstrap/providers.php`, `public/index.php`, `artisan`: bootstrap HTTP dan CLI Laravel.

Penjelasan alur dan isi setiap template tersedia di [docs/CODEBASE.md](docs/CODEBASE.md).

Panduan data dan UI katalog Skin tersedia di [docs/SKIN.md](docs/SKIN.md).

Alur bersama Skin/Shape, palet warna, dan hero komposisi dokter dijelaskan di [docs/TREATMENTS.md](docs/TREATMENTS.md).

Panduan hero satu layar, slideshow, dan komposisi enam dokter Beranda tersedia di [docs/HERO.md](docs/HERO.md).

Panduan ukuran/outline dokter tersedia di [docs/DOCTORS-SOCIAL.md](docs/DOCTORS-SOCIAL.md). Layout katalog dan Instagram terbaru dijelaskan di [docs/LATEST-REFERENCE.md](docs/LATEST-REFERENCE.md).

Kartu halaman Doctors membuka modal foto/jadwal, Education, dan Expertise dengan efek zoom. Panduan aset serta pengisian isi tersedia di [docs/DOCTOR-MODALS.md](docs/DOCTOR-MODALS.md).

Galeri berisi enam foto hasil fetch dari `anareclinic.id` dan `anareshape.id`, dengan cache lokal dan permalink asli. Pembaruan otomatis dapat diaktifkan melalui token masing-masing akun di `.env`.

## Verifikasi lokal

```powershell
composer validate --no-check-publish
composer check-platform-reqs
php artisan route:list --except-vendor
php artisan view:cache
php artisan view:clear
```

Setup lokal telah diverifikasi dengan Laravel 12.69.3, PHP 8.5.7, dan Composer 2.10.1. Pada setup awal, seluruh 19 URL halaman yang ditautkan frontend dan 28 aset lokal yang dirujuk mengembalikan HTTP 200; `/up` mengembalikan HTTP 200; route yang tidak terdaftar mengembalikan HTTP 404.

Setelah penyesuaian dengan foto asli dan teks terbaru, sembilan URL yang mencakup seluruh template diuji di browser Edge/Playwright pada lebar 320, 390, 480, 768, 800, 801, 1024, dan 1440px. Seluruh 72 kombinasi lolos pemeriksaan overflow horizontal, teks terpotong, konten kartu bertumpuk, dan gambar rusak. Menu touch, dropdown, Escape, perubahan viewport, filter, font Cormorant Garamond, tautan WhatsApp, dan data kontak juga diverifikasi. Google Maps berhasil menampilkan lokasi klinik ketika bagian peta dibuka. Detail perbaikan tersedia di [docs/UI-CHANGES.md](docs/UI-CHANGES.md).

Katalog/detail Skin dan layout tablet kemudian diverifikasi melalui server pengguna yang sudah berjalan: delapan URL pada sebelas ukuran 320–1440px, total 88 kombinasi. Pengujian mencakup enam tautan treatment, kategori, accordion, Back to List, data pagination, dan URL tidak valid.

Slideshow tiga foto beserta header transparan sebelumnya diuji pada 44 kombinasi halaman/viewport. Versi terbaru mempertahankan hero satu layar untuk Beranda/Doctors, sedangkan katalog Skin/Shape memakai header biasa dan banner 420px sesuai screenshot terbaru. Autoplay, loop, kontrol, keyboard, serta swipe tersedia.

Ukuran siluet dokter dinormalisasi. Outline putih dan tooltip nama sementara hanya muncul saat hover/fokus atau tap. Instagram kini menggunakan enam thumbnail hasil fetch dua akun, dengan endpoint refresh API opsional dan cache. Layout terbaru diperiksa pada 45 kombinasi halaman/viewport, termasuk kegagalan refresh.

Skin/Shape kemudian disatukan pada template katalog/detail yang sama dengan palet biru dan beige–cokelat. Pemeriksaan tambahan mencakup 49 kombinasi halaman/viewport, alur treatment, warna Shape, serta batas bawah dokter yang rapat dengan section. Hero Doctors diawali foto komposisi enam dokter dengan versi portrait tersendiri.

Fitur bilingual kemudian lolos 108 kombinasi halaman/bahasa/viewport pada lebar 320–1440px. Pengujian mencakup persistensi session/cookie, query dan anchor, form tanpa JavaScript, CSRF, validasi redirect, konten treatment, serta label modal/slideshow/menu. Detail tersedia di [docs/LOCALIZATION.md](docs/LOCALIZATION.md).

Jika ada konfigurasi lama yang masih terbaca, jalankan `php artisan optimize:clear`. Log aplikasi berada di `storage/logs/laravel.log`.

## Kondisi fitur saat ini

- Isi layanan, biography dokter, artikel, dan ulasan masih berupa data statis atau placeholder. Nama/jadwal dokter 1–5 mengikuti aset jadwal yang diberikan; dokter 6 ditandai dummy.
- Galeri Instagram dirender dari cache hasil fetch kedua akun. Cache tanpa token tidak menyegarkan dirinya sendiri; pembaruan otomatis memerlukan token akun profesional masing-masing akun.
- Tombol booking Home menuju WhatsApp `+628113210288`; belum ada penyimpanan appointment atau admin backend.
- Slug detail Skin dan Shape divalidasi terhadap konfigurasi masing-masing; kategori dan pagination mengikuti data. Profil dokter memakai data `config/doctors.php`; modal memakai jadwal asli 1–5 dan dokter 6 dummy. Biography/keahlian masih dapat dilengkapi. Artikel masih memakai template demo umum.
- Katalog Skin saat ini berisi enam treatment Acne; kategori lain dan halaman tambahan aktif setelah datanya ditambahkan. Foto Before/After yang tersedia diberi label Foto ilustrasi.
- Lokasi memakai embed Google Maps yang interaktif dan tautan untuk membuka Maps.
- Foto dan logo klinik memakai berkas di `public/images`; foto dokter Beranda mempunyai derivative transparan yang dinormalisasi. Konten Instagram berasal dari provider Instagram. Judul, paragraf, CTA, nama, ulasan, dan footer berupa HTML/Blade yang dapat diedit.
