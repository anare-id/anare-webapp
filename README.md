# Anare Clinic — Laravel CMS

Laravel 12, Blade, CSS, dan JavaScript vanilla. Konten website dan panel admin menggunakan database MariaDB/MySQL. Node.js dan build frontend tidak diperlukan.

## Hosting dev dan auto-deploy

Laravel staging tersedia di **https://dev.anare.co.id**. Push ke `main` menjalankan GitHub Actions: tes → build production → FTPS terenkripsi → aktivasi release oleh cron hosting → health check. File upload, session, `.env`, dan database tidak ditimpa oleh update kode. Domain utama `anare.co.id` masih WordPress sampai cutover production disetujui terpisah. Detail backup, Cloudflare, secret, batas hosting, dan rollback yang sudah diuji ada di [deploy/README.md](deploy/README.md).

## Menjalankan di komputer ini

Database MySQL XAMPP, `.env`, dependency, migrasi, data awal CMS, serta empat akun demo per role sudah disiapkan di komputer ini. Lihat bagian **Akun demo per role** untuk login panel.

Buka terminal **PowerShell** dari folder proyek:

```powershell
cd D:\project\anare-webapp
composer run dev
```

- Website: **http://127.0.0.1:8000**
- Panel: **http://127.0.0.1:8000/admin**
- Health check: **http://127.0.0.1:8000/up**

Pastikan modul **MySQL** di XAMPP Control Panel berjalan. Biarkan terminal server tetap menyala; `Ctrl+C` menghentikan server. Tidak perlu mengulang setup/migrasi setiap membuka proyek.

Jika memakai **Git Bash**, cegah konversi cookie path `/` menjadi path instalasi Git:

```bash
MSYS2_ENV_CONV_EXCL='*' composer run dev
```

Jika port 8000 sudah dipakai, server yang ada mungkin masih berjalan. Untuk memakai port lain secara eksplisit:

```powershell
php artisan serve --host=127.0.0.1 --port=8001
```

Sesuaikan `APP_URL` di `.env`. `artisan serve` untuk pengembangan lokal, bukan server production.

## Database MySQL XAMPP

Koneksi aplikasi ke MySQL XAMPP telah diverifikasi. Database `anare_clinic` berisi tabel CMS dan RBAC; akun database `anare_app` menggunakan password yang sudah dikonfigurasi di `.env`.

| Parameter | Nilai |
| --- | --- |
| Engine lokal | MariaDB 10.4.32, modul MySQL XAMPP |
| Host | `127.0.0.1` |
| Port | `3306` |
| Database | `anare_clinic` |
| Username aplikasi | `anare_app` |
| Password database | `DB_PASSWORD` yang sudah ada di `.env` |
| Character set / collation | `utf8mb4` / `utf8mb4_unicode_ci` |

Akun aplikasi hanya diberi akses ke `anare_clinic`, bukan menggunakan root. Password database tidak sama dengan password login panel. Jangan commit `.env`, menyalin password ke kode, atau membagikan file koneksi yang menyertakan password.

Gunakan Navicat untuk melihat data, query, dan backup. Gunakan **Laravel migrations** untuk perubahan struktur; mengubah skema manual di Navicat dapat membuat instalasi lain tidak konsisten.

PHP CLI aktif adalah `C:\php\php.exe`, dengan konfigurasi `C:\php\php.ini`. Ekstensi `pdo_mysql` sudah diaktifkan. Sesudah perubahan driver PHP, restart development server yang sudah berjalan agar proses server memuat driver baru.

Migrasi dan seeder awal sudah dijalankan. Pembaruan foto lokasi menambahkan migrasi pengisian `contact.location_image` pada JSON pengaturan; jalankan `php artisan migrate` pada instalasi yang diperbarui. File SQLite lama tidak berisi tabel aplikasi, sehingga tidak ada record SQLite yang perlu dipindahkan.

Data awal yang diimpor: 15 treatment (termasuk satu detail legacy Shape), 6 dokter, 3 artikel, 3 ulasan Google, serta 4 role bawaan. Empat akun demo di komputer ini dibuat terpisah melalui `DemoUsersSeeder`. Pengujian menggunakan database sementara terisolasi, PHP XAMPP, dan ekstensi GD per proses:

```powershell
& "C:\xampp\php\php.exe" -d extension=gd vendor/phpunit/phpunit/phpunit
```

## Panel admin

Login memeriksa akun di tabel `users` dengan password hash Laravel. Akun demo di bawah benar-benar tersimpan di MySQL dan memakai hak akses role masing-masing.

### Akun demo per role

Buka **http://127.0.0.1:8000/admin**:

| Role | Username | Password demo |
| --- | --- | --- |
| Super Admin | `superadmin` | `SuperAdmin123!` |
| Admin | `admin` | `AdminDemo123!` |
| Editor | `editor` | `EditorDemo123!` |
| Viewer | `viewer` | `ViewerDemo123!` |

Seeder khusus demo lokal dapat dijalankan secara eksplisit:

```powershell
php artisan db:seed --class=DemoUsersSeeder
```

Seeder membuat username yang belum ada dan menetapkan satu role. Menjalankannya lagi tidak menggandakan akun atau menimpa password, status aktif, profil, maupun role akun yang sudah ada. Seeder ini terpisah dari `DatabaseSeeder`, sehingga seed konten biasa tidak otomatis membuat akun demo.

Untuk membuat akun pemilik dengan kredensial sendiri pada instalasi baru, gunakan `php artisan admin:create --role=super-admin` dan isi prompt.

Untuk membuat administrator tambahan dari terminal terpercaya (role default `admin`):

```powershell
php artisan admin:create --role=admin
```

Isi nama, username unik, email opsional, dan password melalui prompt. Password harus 12–72 karakter, maksimal 72 byte, berisi huruf besar/kecil, angka, dan simbol. Input password tidak ditampilkan.

| Menu / konten | Penyimpanan |
| --- | --- |
| Login administrator | `users` |
| Dashboard / riwayat perubahan | Agregasi konten dan `activities` |
| Beranda: slideshow/banner | `hero_slides`, dibedakan berdasarkan section |
| Beranda: kartu layanan | `services` |
| Beranda: dokter/artikel unggulan | Flag `featured` pada `doctors` / `articles` |
| Beranda: ulasan | `reviews`, rating 1–5 |
| Skin / Shape | `treatments`, relasi `category_id`, dipisahkan kolom `service` |
| Kategori Skin / Shape / Artikel | `categories`, dipisahkan kolom `group` |
| Dokter, jadwal, pendidikan, keahlian | `doctors` |
| Artikel, isi, penulis, tanggal | `articles` |
| Judul section, pagination, kontak, Maps, Instagram | `site_settings`, per grup pengaturan |
| Foto upload | File `storage/app/public/cms`; path pada record terkait |

Simpan menulis ke server, bukan `localStorage`. Konten **Dipublikasikan** langsung dibaca website; **Draft** tidak ditampilkan dan URL detailnya mengembalikan 404. Duplikasi menghasilkan draft dengan slug unik. Menghapus kategori yang masih dipakai ditolak.

Banner katalog Skin/Shape memakai satu judul dan deskripsi dari **Atur halaman** (`site_settings` grup `skin`/`shape`). Foto, urutan, status, dan crop mengikuti `hero_slides`. Teks/CTA masing-masing slide tidak ditumpuk di atas judul katalog; judul slide tetap digunakan sebagai alt foto. Hero Skin/Shape sekarang memakai tinggi satu layar `100svh` (fallback `100vh`), sama seperti Beranda dan Dokter, dengan navbar di atas foto dan kontrol slideshow selalu tersedia. Logo Instagram/WhatsApp di footer tampil tanpa kotak latar putih.

Halaman Shape dan detailnya menggunakan `logo-shape-transparent.png`, hasil penghapusan latar dari aset asli `logo-shape.jpeg`. Header transparan menggunakan versi warna putih melalui CSS; header biasa dan footer memakai warna asli logo. Footer Shape berwarna taupe `#9b897f`, sedangkan Beranda, Dokter, dan Artikel memakai abu-abu `#898989`. Footer Skin mempertahankan tema birunya.

Beranda dan endpoint ulasan hanya menampilkan dua ulasan berstatus dipublikasikan, mengikuti urutan CMS; semua record tetap tersedia di admin. Kartu lokasi menampilkan foto resepsionis Anare Clinic di atas peta. Sumber foto tercatat di `resources/data/google-place-photo.json`. Foto dapat diganti lewat **Pengaturan → Kontak & lokasi → Ubah foto klinik**; path disimpan sebagai `contact.location_image`, divalidasi sebagai gambar lokal, dan tidak menimpa pengaturan kontak lainnya.

Peta memprioritaskan `contact.map_embed` dan menggunakan pin ANARE clinic (CID `3421534276469252101`) pada koordinat Rungkut `-7.3234692,112.776346`, zoom 17. Tautan `place_id` lama dinormalisasi agar iframe tidak kembali ke peta dunia. URL embed dapat dikelola terpisah dari tautan Buka di Maps. Akun sosial publik yang digunakan adalah `anareclinic.id` dan `anareshape.id`; migrasi pembaruan menghapus tautan `anareskin.id` yang tidak tersedia. Kartu dokter hanya menampilkan foto dan nama, tanpa label generik di bawah nama. Logo Shape memakai frame navbar/footer yang sama dengan logo utama, dengan padding transparan dan skala lebih kecil.

### Notifikasi aksi CMS

Setiap tambah, edit, hapus, duplikasi, simpan pengaturan, perubahan dokter pilihan, dan upload menampilkan notifikasi sukses atau gagal. Pesan sukses berasal dari respons server dan menyebutkan tindakan yang selesai. Akun pengguna serta role menggunakan feedback yang sama, termasuk setelah pengalihan halaman.

- **Hijau / Berhasil** untuk tindakan yang selesai; **merah / Gagal** untuk validasi, izin, sesi kedaluwarsa, data tidak ditemukan, pembatasan request, gangguan server, atau koneksi terputus.
- Pesan validasi juga tampil di form dan menandai kolom terkait. Kegagalan mempertahankan input serta editor agar dapat diperbaiki dan disimpan kembali.
- Toast tetap berada di atas native dialog, responsif pada mobile, dapat ditutup, dan menggunakan `role="status"` atau `role="alert"`. Timeout 7 detik untuk sukses dan 12 detik untuk gagal; berhenti sementara saat toast di-hover atau mendapat fokus. Maksimal tiga notifikasi tampil bersamaan.
- Form akun/role tetap mendukung submit biasa tanpa JavaScript, dengan pesan sukses dan validasi server di halaman.

Implementasi ada di `public/js/admin.js`, `public/css/admin.css`, layout admin, dan respons controller CMS. Regresi API diuji di `tests/Feature/CrudFeedbackTest.php`. Suite lengkap lulus **47 tes / 433 assertion**; verifikasi browser mencakup mutasi nyata konten/kategori/akun/role, error HTTP 403/404/419/429/500, network error, respons tidak valid, notifikasi upload dalam dialog, dan fallback tanpa JavaScript. Data sementara verifikasi dibersihkan.

Konten memiliki kolom Indonesia dan Inggris. Saat EN aktif, nilai Inggris dipakai jika terisi; jika kosong, nilai Indonesia dipakai. Mengubah kolom Indonesia tidak menimpa terjemahan Inggris yang sudah ada. Label UI tetap berasal dari `lang/id/site.php` dan `lang/en/site.php`.

Upload menerima JPG, PNG, WebP, dan GIF maksimal 2 MB; MIME/ekstensi diverifikasi server. Gambar disimpan sebagai file, bukan base64 di database. Field gambar boleh kosong; website tidak merender gambar rusak jika belum diisi. Penghapusan konten tidak otomatis menghapus file yang mungkin dipakai record lain.

### Optimasi gambar otomatis

Upload JPEG dinormalisasi mengikuti orientasi EXIF, diperkecil proporsional ke sisi maksimal 2560px tanpa upscale, dan dikodekan WebP kualitas 88. PNG serta WebP statis memakai WebP lossless tanpa mengurangi dimensi supaya transparansi, logo, dan teks jadwal tetap tajam. GIF dan WebP animasi dipertahankan agar tidak menjadi gambar diam. Jika konversi tidak lebih kecil dan tidak perlu resize/rotasi, file asli yang lebih efisien dipakai. Foto menggunakan kompresi lossy kualitas tinggi: kualitas visual ditargetkan tetap baik, bukan jaminan piksel identik. Upload dibatasi 12 megapiksel dan anggaran memory server selain batas file 2 MB untuk menghindari kehabisan memori saat decode.

`php -d memory_limit=512M artisan images:optimize` membuat salinan ringan untuk aset JPG/PNG existing; sumber asli tidak diubah. Manifest mengarahkan frontend dan galeri CMS ke derivative bernama hash. GitHub Actions menjalankan perintah ini setiap build production sehingga tidak perlu commit hasil generate. Derivative hash memakai cache satu tahun immutable; perubahan isi menghasilkan URL baru. Slideshow memprioritaskan foto pertama, bukan memaksa semua foto menjadi eager.

Pengukuran lokal: 55 aset dioptimalkan; total sumber yang diperiksa 153.546.651 byte menjadi 21.224.778 byte untuk versi tersaji (sekitar 86% lebih kecil). `hero.jpg` turun dari 5.516.328 ke 261.070 byte. Tes mencakup rasio/dimensi, alpha PNG, warna lossless, orientasi EXIF, dan GIF tidak diratakan. Untuk pemakaian lokal setelah checkout baru, jalankan perintah generator di atas.

Semua endpoint perubahan memakai autentikasi session, pemeriksaan permission Spatie, dan CSRF. Validasi menggunakan FormRequest; penulisan konten dan catatan aktivitas menggunakan transaksi database. Slug unik dan relasi kategori dilindungi constraint database. Tidak ada registrasi publik. Akun tanpa `admin.access` atau akun nonaktif tidak dapat masuk panel meskipun password benar.

### Role dan hak akses

Spatie Laravel Permission 6 dipakai dengan guard session Laravel `web`. Role, permission, dan assignment disimpan pada tabel `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, dan `role_has_permissions`. Tidak ada pengecekan privilege berdasarkan username.

| Role bawaan | Hak akses |
| --- | --- |
| `super-admin` | Seluruh konten dan pengelolaan akun/role |
| `admin` | Kelola, publish, dan hapus konten; pengaturan website; tanpa pengelolaan akun/role |
| `editor` | Baca, buat, dan edit draft; upload; duplikat konten menjadi draft; tanpa publish/hapus/pengaturan website |
| `viewer` | Baca konten dan pengaturan saja |

Editor tidak boleh mengubah record yang sudah dipublikasikan, termasuk flag unggulan; mengubah isi live tanpa permission publish akan melewati kontrol publikasi. Perubahan kategori yang dipakai konten live dan pengaturan section juga memerlukan permission publish. Menu/tombol mengikuti akses akun, tetapi server tetap memeriksa semua request langsung dan mengembalikan 403 jika ditolak. State JSON hanya menyertakan modul yang boleh dibaca akun tersebut.

Menu induk **Management User** memuat tiga submenu terpisah: **User** (`/admin/users`) untuk akun/role/status/password, **Role** (`/admin/roles`) untuk nama dan penggunaan role, serta **Hak Akses** (`/admin/permissions`) untuk katalog permission dan penugasannya ke role. Semua halaman menampilkan tabel terlebih dahulu dengan pencarian, filter relevan, dan pagination server 10/20/50 baris. Form tambah/edit hanya muncul setelah tombol aksi dipilih. User memerlukan `users.manage`; Role dan Hak Akses memerlukan `roles.manage`; seluruhnya tetap khusus role `super-admin`. Permission pengelolaan langsung pada akun biasa tidak menjadikannya Super Admin.

Aksi baris memakai komponen existing `.table-actions .icon-button`: ikon mata untuk detail read-only, pensil untuk edit, roda gigi untuk hak akses, dan tempat sampah untuk hapus. View, edit, tambah User/Role, serta editor hak akses tampil dalam modal di atas tabel. Modal dapat ditutup dengan tombol tutup, Batal, Escape, atau klik backdrop; penutupan menghapus parameter modal dari URL tanpa menghapus filter tabel. Validasi gagal membuka kembali modal record yang sama dan mempertahankan input, kecuali password. Ukuran tombol 29×29px, ikon 15px, serta pagination chevron mengikuti tabel Skin/Artikel; tooltip dan label aksesibel tetap tersedia.

Mengubah nama role tidak menghapus permission yang sudah ditetapkan. Klik ikon roda gigi pada tabel Role untuk membuka modal **Hak akses role**. Permission dikelompokkan dalam accordion per modul: Akses Panel, Dashboard, Aktivitas, Beranda, Skin, Shape, Dokter, Artikel, Pengaturan, dan Media. Setiap header menampilkan jumlah izin dipilih yang langsung diperbarui; beberapa modul dapat dibuka sekaligus. Menutup accordion tidak menghapus checkbox maupun mengecualikannya dari penyimpanan. Tombol Batal/Simpan tetap terlihat saat konten modal digulir. Melepas semua centang mencabut seluruh izin role. Kode permission tetap kontrak aplikasi, bukan CRUD bebas. Super Admin memakai accordion read-only dengan checkbox dinonaktifkan. Browser telah memverifikasi buka/tutup, perubahan jumlah, simpan pilihan dari grup tertutup, persistensi setelah dibuka ulang, dan mobile 390px tanpa overflow; izin yang dipakai untuk pengecekan dikembalikan seperti semula.

Role `super-admin` tidak dapat diubah/dihapus melalui panel. Administrator aktif terakhir dengan role tersebut tidak dapat dinonaktifkan atau diturunkan rolenya. Role yang masih dipakai akun tidak dapat dihapus. Perubahan akun/role dicatat sebagai aktivitas. Akun dinonaktifkan, bukan dihapus, untuk menjaga riwayat perubahan.

Migrasi upgrade memberikan role `super-admin` kepada akun yang sudah ada sebelum RBAC dipasang. Akun baru tidak otomatis mendapat privilege; role harus ditetapkan secara eksplisit. Pada instalasi baru, buat pemilik awal dengan:

```powershell
php artisan admin:create --role=super-admin
```

Password login existing tidak diganti oleh migrasi role. Permission bawaan dibuat saat instalasi; menjalankan seeder konten lagi tidak mengembalikan permission custom ke default. Setelah perubahan permission manual melalui SQL/Navicat, jalankan `php artisan permission:cache-reset`; perubahan dari panel menggunakan API Spatie yang mengelola cache-nya.

### Palet warna website

Pada **Pengaturan → Palet warna website**, tersedia color picker native dan input HEX `#RRGGBB` yang sinkron untuk 42 token warna: elemen bersama, Beranda, Skin, dan Shape. Preview menampilkan warna pilihan sebelum disimpan. Tombol **Reset ke default** hanya mengembalikan pilihan di form; klik **Simpan palet warna** untuk menerapkannya ke database. Perubahan disimpan pada `site_settings` grup `theme` dan langsung dipakai website lewat CSS variables per request, tanpa rebuild atau perubahan file CSS.

Admin/Super Admin dengan `settings.update` dapat mengubah palet; Viewer dengan `settings.view` hanya membaca. Editor tanpa akses pengaturan tidak menerima data palet. Server menolak kode selain enam digit HEX untuk mencegah injeksi CSS. Default mengikuti warna existing di `config/theme.php`; foto, logo, dan warna panel admin tidak diubah. Skin dan Shape punya warna aksen, teks, latar, kartu, border, pagination, accordion, before/after, serta footer terpisah; warna teks putih pada foto tetap dipertahankan. Periksa keterbacaan/kontras setelah memilih warna terang.

## Setup di komputer baru

Kebutuhan: PHP 8.2+, Composer 2, MariaDB/MySQL, ekstensi `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `dom`, dan `zip`. Tes memakai `pdo_sqlite` untuk database sementara terisolasi; tes upload juga memakai `gd`.

1. Buat database `anare_clinic` berkarakter `utf8mb4` beserta user khusus melalui Navicat atau administrator database.
2. Dari folder proyek:

   ```powershell
   composer run setup
   ```

   Perintah ini menginstal dependency, membuat `.env` jika belum ada, dan menghasilkan `APP_KEY`. Jalankan untuk instalasi awal saja; jangan mengganti key pada instalasi yang sudah dipakai.

3. Isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` pada `.env` sesuai server tersebut.
4. Jalankan:

   ```powershell
   php artisan migrate --seed
   php artisan storage:link
   php artisan admin:create --role=super-admin
   composer run dev
   ```

Seeder mengimpor konten/foto existing dari snapshot `database/seeders/data/`, kamus bahasa, dan data ulasan yang sudah diambil. Marker `site_settings._seed` membuat impor hanya berjalan sekali; menjalankan seed lagi **tidak menimpa edit dan tidak menghidupkan kembali konten yang sengaja dihapus**. Jangan gunakan `migrate:fresh` pada database yang berisi data penting.

Session/cache menggunakan file dan queue menggunakan `sync`; tidak memerlukan tabel session/cache atau queue worker. Direktori `storage/` dan `bootstrap/cache/` harus dapat ditulis. Backup **database dan `storage/app/public`** bersama agar foto upload tidak hilang.

## Sumber data dan lokasi kode

- `database/migrations/`: struktur tabel, foreign key, index, dan unique constraint.
- `database/seeders/DatabaseSeeder.php`, `database/seeders/data/`: impor awal saja, bukan sumber konten runtime.
- `app/Models/`: model dan relasi Eloquent.
- `app/Http/Requests/`, `app/Rules/`: validasi konten, kategori, pengaturan, path gambar, dan tautan.
- `app/Services/CmsEditor.php`: transaksi CRUD, kategori, pengaturan, upload, dan aktivitas.
- `app/Services/CmsContent.php`, `app/Http/Resources/`: state panel dari database.
- `app/Services/WebsiteContent.php`: konten terlokalisasi dan pengaturan website per request.
- `app/Http/Controllers/AdminController.php`, `AdminContentController.php`: autentikasi, halaman panel, endpoint CMS.
- `app/Http/Controllers/HomeController.php`, `TreatmentController.php`, `WebsiteController.php`: halaman publik berbasis database.
- `routes/web.php`: rute publik dan CMS yang dilindungi middleware admin.
- `resources/views/admin/`, `public/js/admin.js`, `public/css/admin.css`: panel dan editor.
- `resources/views/pages/`, `resources/views/partials/`, `resources/views/layouts/`: website publik.
- `public/images/`: aset awal; `/storage/` melayani upload melalui `public/storage`.
- `tests/Feature/CmsTest.php`: pengujian perilaku CMS dengan database SQLite in-memory, tidak menyentuh MariaDB lokal.

Galeri Instagram tetap memakai cache postingan hasil fetch; refresh opsional memerlukan token akun profesional di `.env`. Link akun berasal dari pengaturan CMS. Ulasan kini otoritatif dari CMS; refresh browser tidak menimpa ulasan editor dengan snapshot/API eksternal. Booking tetap berupa tautan WhatsApp, bukan sistem appointment.

## Verifikasi

```powershell
composer validate --no-check-publish
composer check-platform-reqs
php artisan migrate:status
php artisan route:list --except-vendor
php artisan test
```

Verifikasi lokal: PHP 8.5.6, Composer 2.9.7, Laravel 12.69.3, MariaDB 12.3.2, Spatie Laravel Permission 6.25.0. **51 feature tests / 320 assertions lulus**, termasuk otorisasi detail user tanpa mengekspos hash password, validasi edit yang mempertahankan record/input, filter gabungan, pagination, rename role tanpa kehilangan akses, serta penugasan/pencabutan permission. Browser memverifikasi modal view User/Role/Hak Akses, edit dan simpan, error nama role duplikat tetap di modal, Escape menutup modal, serta layout mobile 390px tanpa overflow. `tests/TestCase.php` menolak tes selain SQLite `:memory:`. Driver coverage belum tersedia.

Verifikasi integrasi terbaru lulus **67 tes / 535 assertion**, termasuk feedback CRUD, empat akun demo, pembatasan dua ulasan, foto lokasi, pin peta Rungkut, dan koreksi akun sosial. Browser memeriksa 96 kombinasi delapan route, dua bahasa dan enam ukuran layar; mencakup logo/footer Shape, warna footer umum, kartu dokter tanpa label generik, kartu lokasi/ulasan, pengaturan foto, serta kompatibilitas notifikasi dengan modal Management User. Pengujian dan render visual menggunakan SQLite in-memory; `phpunit.xml` mengisolasi path cache konfigurasi/route agar cache aplikasi lokal tidak dipakai saat tes.

Smoke browser mencakup login akun database, artikel baru ID/EN, persistensi setelah reload, publikasi ke halaman detail, draft 404, hapus artikel, upload file nyata dan pemuatan publik, tambah/hapus kategori, serta pengaturan jam operasional per bahasa. Editor treatment, hero, dan dokter berhasil menyimpan konten existing; seluruh 10 koleksi menerima save terautentikasi; request perubahan tanpa CSRF ditolak 419. Data sementara dibersihkan dan pengaturan semula dikembalikan. Delapan template publik diperiksa tanpa gambar rusak/overflow desktop; Home dan panel pengaturan juga diperiksa pada lebar 390px.

Smoke RBAC di browser: login akun existing sebagai Super Admin; buat akun Viewer dengan password hash; buat role custom dan buktikan tetap ada setelah reload; Viewer tidak memiliki tombol mutasi dan request langsung mendapat 403; ubah akun menjadi Editor, simpan draft nyata, dan pastikan publish ditolak 403 serta detail draft 404; penurunan Super Admin aktif terakhir ditolak 422. Form akun diperiksa pada lebar 390px tanpa overflow. Draft, akun, dan role verifikasi sementara dibersihkan; akun pemilik existing dan passwordnya tidak berubah.

Verifikasi tabel management: tiga halaman diperiksa pada lebar 390, 768, dan 1440px (sembilan kombinasi), seluruhnya HTTP 200 tanpa overflow viewport. Browser membuktikan pencarian role, create role, penugasan dua permission, persistensi setelah reload, pagination katalog, serta dialog konfirmasi hapus role. Role verifikasi dihapus setelah pengecekan. Saat verifikasi awal terdapat kebocoran konfigurasi test runner ke MariaDB lokal; isolasi dan guard database sudah diperbaiki, lalu konten seed dan akun pemilik lokal dipulihkan. Edit manual yang tidak tercakup snapshot seed tidak dapat dipulihkan dari seed.

## Troubleshooting

- **Connection refused / SQLSTATE**: cek service MariaDB, port, dan `DB_*` pada `.env`; coba Test Connection di Navicat.
- **SQLSTATE 1045 / Access denied / using password: NO**: pastikan akun MySQL `anare_app` tersedia untuk koneksi lokal, memiliki akses ke `anare_clinic`, dan passwordnya sesuai `DB_PASSWORD` di `.env`. Setelah mengubah kredensial, jalankan `php artisan config:clear` lalu `php artisan config:cache`. Akun MySQL server terpisah dari akun admin CMS dan tidak ikut dibuat hanya dengan mengimpor tabel aplikasi.
- **Aria checksum pada tabel hak akses**: pada pemulihan lokal terakhir, `mysql.db` telah dicadangkan dan diperbaiki; hak akses aplikasi dipasang ulang. Pemeriksaan tabel sistem dan tabel utama CMS kembali berstatus OK.
- **Konfigurasi lama**: `php artisan optimize:clear`.
- **Foto upload 404**: jalankan `php artisan storage:link`, pastikan file ada di `storage/app/public`.
- **419 saat save**: reload halaman dan login lagi jika session habis.
- **Error cookie path Git**: gunakan perintah Git Bash di atas atau terminal PowerShell native.
- Log aplikasi: `storage/logs/laravel.log`.

Data awal masih memuat teks contoh dari website sebelumnya; memindahkan ke database tidak membuat informasi klinisnya otomatis valid. Lengkapi konten melalui panel dan periksa materi medis sebelum dipublikasikan.
