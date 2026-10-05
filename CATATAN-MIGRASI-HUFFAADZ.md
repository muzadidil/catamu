# Catatan Migrasi: zasha.online → huffaadz.com

Status: **ANALISIS, belum eksekusi.** Dibuat 2026-10-05 supaya konteks ini tidak
perlu dijelaskan ulang di percakapan berikutnya.

## Cara kerja yang disepakati

1. Claude (di sesi ini) **hanya menganalisis dan menyusun prompt**.
2. Eksekusi nyata di kedua akun hosting (klik hPanel, jalankan SSH) dilakukan oleh
   **agent AI terpisah** yang dipegang user.
3. Urutan: (1) analisa tuntas → (2) bersih-bersih zasha.online/adatamu.id → (3)
   deploy ke hosting huffaadz.com.

Jadi keluaran dari sesi ini bukan perubahan di server, melainkan dokumen
analisis + teks prompt siap pakai untuk agent itu.

## Peta akun

| Akun Google | Isi | Catatan |
|---|---|---|
| `muzadidilfuad@gmail.com` | Hosting + domain **zasha.online** (Hostinger) | Tempat CATAMU "numpang" sekarang: `~/domains/catamu.zasha.online/public_html`. SSH: `u607709216@sg-nme-web502`. |
| `qolby.mitra@gmail.com` | Domain **adatamu.id** (dibeli di sini) **+** hosting & domain **huffaadz.com** (baru dibeli, juga akun bisnis) | Target migrasi. SSH baru yang sudah diberikan user: host `145.223.108.130`, port `65002`, user `u774868168`. Kemungkinan besar ini akun hosting huffaadz.com, tapi **belum dikonfirmasi**. |

Riwayat lengkap setup zasha.online/adatamu.id yang sudah berjalan ada di
[CATATAN-DEPLOY-ADATAMU.md](CATATAN-DEPLOY-ADATAMU.md) — termasuk pola
"numpang" (app di satu docroot, domain lain diarahkan via A record + symlink)
dan jebakan-jebakan yang sudah ditemukan (IP CDN vs IP asli, docroot subdomain
bersarang, SESSION_DOMAIN perlu titik di depan, dll). Dokumen itu masih jadi
rujukan teknis utama; dokumen ini hanya menambahkan lapisan "sekarang mau
pindah ke akun lain".

## Kondisi saat ini (live, jangan sampai rusak saat migrasi)

- `https://adatamu.id`, `/login`, `admin.adatamu.id`, `cekin.adatamu.id` — semua
  jalan di hosting **zasha.online** (akun `muzadidilfuad@gmail.com`), diarahkan
  dari domain **adatamu.id** (akun `qolby.mitra@gmail.com`) via A record.
- Ada user aktif (owner, admin, tamu check-in) dan data di `storage/` (foto
  tamu, tanda tangan, logo kantor, bukti transfer) + database — **bukan
  instalasi kosong**.
- Login Google (OAuth) terdaftar untuk redirect URI `adatamu.id`.

## Tujuan migrasi

Pindahkan **hosting** aplikasi CATAMU dari akun `muzadidilfuad@gmail.com`
(zasha.online) ke akun `qolby.mitra@gmail.com` (hosting huffaadz.com), dengan:

- Domain **adatamu.id** tetap dipakai sebagai alamat produksi (tidak ganti
  nama domain untuk pengguna akhir).
- Pola "numpang" yang sama seperti sebelumnya: app ditaruh di root hosting
  huffaadz.com, lalu domain adatamu.id (dan admin./cekin. subdomain-nya)
  diarahkan ke situ — **tanpa mengganggu** domain/hosting huffaadz.com yang
  sudah ada untuk keperluan lain.
- Setelah migrasi berhasil dan diverifikasi, bersihkan sisa instalasi lama di
  akun zasha.online.

Karena domain adatamu.id dan hosting tujuan (huffaadz.com) sekarang ada di
**akun yang sama** (`qolby.mitra@gmail.com`), seharusnya migrasi ini lebih
sederhana dibanding setup awal — tidak perlu lagi verifikasi TXT lintas akun
seperti waktu adatamu.id pertama kali didaftarkan ke hPanel zasha.online.

## Hal yang masih tercatat tidak konsisten di dokumen lama

`CATATAN-DEPLOY-ADATAMU.md` sempat menyebut dua hal berbeda soal DNS
adatamu.id:

- Bagian "langkah 2" menyebut DNS dikelola di **Niagahoster**
  (`qolby.reload@gmail.com`).
- Bagian "Pertanyaan yang sudah terjawab" (lebih baru) menyebut domain
  **adatamu.id ada di akun Hostinger `qolby.mitra@gmail.com`**, nameserver
  bawaan Hostinger (`aster.dns-parking.com` / `helios.dns-parking.com`).

Belum dicek ulang yang mana yang benar sekarang. Ini perlu dipastikan sebelum
menyusun langkah DNS, karena menentukan di panel mana A record adatamu.id
harus diubah.

## Keputusan yang sudah dikonfirmasi user (2026-10-05)

- **huffaadz.com sudah ada situs live** di domain huffaadz.com itu sendiri.
  Jadi adatamu.id **wajib** jadi website/addon domain terpisah di hPanel akun
  ini — bukan ditumpuk di docroot yang sudah dipakai huffaadz.com.
- **SSH `u774868168@145.223.108.130:65002` dikonfirmasi** = akun hosting
  huffaadz.com (`qolby.mitra@gmail.com`). Password sudah dipegang user,
  **sengaja tidak dicatat di file ini** (repo ini publik).
- Setelah huffaadz.com terverifikasi jalan, instalasi lama di zasha.online
  **dihapus total** (bukan dibiarkan sebagai cadangan).
- DNS adatamu.id: **masih belum dipastikan ulang** (Hostinger hPanel
  `qolby.mitra@gmail.com` vs Niagahoster `qolby.reload@gmail.com`). Dijadikan
  langkah verifikasi pertama di Prompt A, bukan ditanyakan ke user dulu —
  agent eksekusi bisa cek langsung di panel yang dipegangnya.

## UPDATE PENTING (2026-10-05, setelah cek langsung via SSH) — ini deploy baru, bukan migrasi data live

Awalnya diasumsikan ini migrasi aplikasi live dengan data pengguna asli
(lihat bagian "Hal paling berisiko" versi lama di bawah — **sudah tidak
berlaku**, disimpan sebagai riwayat). Setelah dicek langsung:

- **Folder `catamu.zasha.online` sudah tidak ada** di akun zasha.online —
  dihapus sendiri oleh user/tim (dikonfirmasi user, sengaja). Situs
  `adatamu.id`/`admin.adatamu.id`/`cekin.adatamu.id` sekarang **404** dari
  luar (symlink `~/domains/adatamu.id/public_html` putus, menunjuk ke folder
  yang sudah tidak ada).
- **User konfirmasi: aplikasi ini belum sepenuhnya live, tidak perlu
  khawatir soal backup data lama.** Jadi deploy ke huffaadz.com adalah
  **instalasi baru** dari repo GitHub, bukan migrasi database+storage dari
  server lama. Langkah dump DB / copy storage dari server lama di Prompt A
  versi draft sebelumnya **tidak diperlukan lagi**.
- Konsekuensinya: tidak perlu lagi fase "siapkan dulu tanpa ubah DNS, baru
  cutover di jam sepi" yang hati-hati seperti migrasi live. Bisa langsung
  pasang DNS begitu docroot & app siap, tidak ada user aktif yang akan
  ke-logout.

## Fakta sebenarnya hasil cek langsung SSH (bukan dari agent panel, bukan asumsi dokumen lama)

Dicek langsung oleh Claude via SSH ke kedua akun (read-only, tidak ada
perubahan apa pun dibuat).

**Akun zasha.online (`u607709216@37.44.245.146:65002`):**
- `~/domains/adatamu.id/public_html` → symlink putus ke
  `~/domains/catamu.zasha.online/public_html` (folder sasarannya tidak ada).
- `~/domains/adatamu.id/public_html.bak/` masih ada (isi: `admin/`, `cekin/`,
  `default.php` — placeholder asli sebelum CATAMU dulu dipasang).
- Tidak ada folder `catamu.zasha.online` di mana pun di home directory
  (`find` sampai depth 5, nihil).
- IP hosting: `37.44.245.146` (cocok dokumen lama).
- MariaDB 10.5. PHP CLI tidak sempat dicek di akun ini (tidak relevan lagi
  karena app sudah dihapus).

**Akun huffaadz.com (`u774868168@145.223.108.130:65002`):**
- `~/domains/huffaadz.com/public_html` → symlink ke
  `/home/u774868168/huffaadz-app/public`. Pola ini (app code di `~/<nama>-app`,
  docroot cuma symlink ke `<app>/public`) **beda** dari pola CATAMU lama
  (yang naruh seluruh repo langsung di docroot). Pertimbangkan pakai pola
  yang sama (`~/catamu-app` + symlink) biar konsisten dengan akun ini,
  **meski pola lama (docroot = root repo) juga tetap bisa dipakai** — CATAMU
  tidak punya folder `public/` sebagai document root terpisah seperti
  Laravel standar (base `index.php` ada di root repo, bukan di `public/`),
  jadi **ikuti pola lama CATAMU** (docroot = root repo, bukan symlink ke
  `public/`) supaya tidak perlu ubah struktur aplikasi.
- Hanya ada 1 entri di `~/domains/`: `huffaadz.com`. **adatamu.id belum jadi
  website terpisah** — perlu ditambahkan dari hPanel (bagian yang perlu akses
  browser/panel, tidak bisa lewat SSH).
- PHP CLI 8.3.33, **tidak ada `php8.2`** (hanya 8.3 tersedia di CLI). Composer
  2.9.8 sudah terpasang. Git 2.47.3 terpasang. MySQL client 11.8.9-MariaDB
  terpasang. Semua tool yang dibutuhkan `deploy/update.sh` sudah ada, tidak
  perlu instal composer.phar manual.
- Kapasitas (dari panel): disk 51.200 MB total, 193 MB terpakai (hampir
  kosong). 0/150 database terpakai. 7/100 subdomain terpakai (7 subdomain
  huffaadz.com yang sudah ada, semua menunjuk ke docroot huffaadz.com yang
  sama — situs multi-tenant, mirip pola Route::domain() di CATAMU).
- **Domain adatamu.id SUDAH terdaftar** di hPanel akun ini (qolby.mitra@gmail.com)
  dan **zona DNS-nya bisa dikelola dari panel yang sama** — jadi tidak perlu
  lagi proses verifikasi TXT lintas akun seperti dulu. Saat ini apex record
  adatamu.id berupa **ALIAS** (bukan A record), status "belum terhubung ke
  hosting" — perlu diubah ke A record mengarah ke `145.223.108.130` saat
  deploy.

## Hal paling berisiko dalam migrasi ini (VERSI LAMA — sudah tidak relevan, lihat UPDATE di atas)

Ini **bukan** deploy aplikasi kosong — CATAMU yang hidup sekarang punya:

- Database dengan user asli (owner, admin, akun tamu check-in).
- File upload di disk (`app/Support/ImageStore.php`, `app/Support/QrisImage.php`)
  berisi foto tamu, tanda tangan, logo kantor, bukti transfer — **tidak ada di
  Git**, harus dipindah manual.
- `APP_KEY` di `.env` lama — dipakai Laravel untuk enkripsi/hashing session &
  cookie. **Harus disalin persis**, bukan dibuat baru, atau data yang
  terenkripsi dengan key lama tidak bisa dibaca lagi.

Karena itu Prompt A di bawah polanya: **siapkan & migrasikan semuanya dulu di
huffaadz.com, verifikasi tanpa mengubah DNS publik, baru cutover DNS di jam
sepi** — bukan pindah DNS duluan baru beres-beres belakangan.

## Prompt Analisa — jalankan ini DULU, sebelum Prompt A/B

Prompt A dan B di bawah masih berisi beberapa asumsi yang belum dicek
langsung di lapangan (path docroot, lokasi DNS, kapasitas hosting baru).
Supaya tidak menyusun langkah eksekusi di atas tebakan, jalankan dulu dua
prompt analisa ini — satu per akun, dua agent AI terpisah, **keduanya
read-only, tidak mengubah apa pun**. Hasilnya ditempel balik ke sini (Claude)
untuk dipakai menyusun ulang Prompt A/B yang akurat.

### Analisa 1 — untuk agent di akun `muzadidilfuad@gmail.com` (zasha.online, server LAMA)

```
Tugas: investigasi read-only kondisi hosting aplikasi Laravel "CATAMU" yang
saat ini live, SEBELUM dimigrasikan ke hosting lain. JANGAN ubah, hapus,
atau tulis apa pun — ini murni pengumpulan fakta untuk perencanaan migrasi.

Akses: SSH u607709216@sg-nme-web502 (password sudah saya pegang terpisah),
plus hPanel akun muzadidilfuad@gmail.com kalau perlu. Folder aplikasi:
~/domains/catamu.zasha.online/public_html.

Kumpulkan dan laporkan dalam bentuk terstruktur (poin per poin, bukan narasi
panjang):

1. Konfirmasi folder aplikasi: `git status`, `git log --oneline -1`, apakah
   working tree bersih.
2. Struktur docroot sesungguhnya saat ini untuk adatamu.id, admin.adatamu.id,
   cekin.adatamu.id: jalankan `ls -la` dan `readlink` di tiap docroot/symlink
   terkait (folder aplikasi, folder domain adatamu.id, symlink admin/cekin,
   dan public_html.bak kalau masih ada). Laporkan path absolut sebenarnya,
   bukan yang didokumentasikan sebelumnya — kalau beda, tandai beda di mana.
3. Isi `.env`: laporkan HANYA nama key yang ada dan apakah nilainya kosong
   atau terisi (misal "APP_KEY: terisi", "DB_HOST: terisi"). JANGAN tempel
   nilai asli APP_KEY, password database, client secret Google, atau
   kredensial lain ke dalam laporan/chat.
4. Database: nama database, versi MySQL/MariaDB (`mysql --version` atau
   `SHOW VARIABLES LIKE 'version'`), ukuran database (`SELECT
   table_schema, SUM(data_length+index_length) FROM information_schema.tables
   GROUP BY table_schema`), dan jumlah baris tabel-tabel utama data pengguna
   (cari nama tabelnya dari `database/migrations/` atau `app/Models/`, fokus
   ke tabel user/owner/admin/tamu/check-in/langganan).
5. File upload di disk: baca `app/Support/ImageStore.php` dan
   `app/Support/QrisImage.php` untuk tahu path penyimpanan sebenarnya, lalu
   laporkan `du -sh <path>` dan jumlah file di masing-masing path itu.
6. IP server sebenarnya sekarang: dari hPanel menu hosting > "Website IP
   address" (bukan hasil `dig`, karena Hostinger pakai CDN di depan domain
   ini — akan dapat IP yang salah kalau pakai dig/nslookup).
7. PHP version aktif di SSH (`php -v`) dan apakah ada PHP 8.2 spesifik
   (`php8.2 -v`) seperti yang disebut di deploy/update.sh.

Laporkan semua temuan di atas secara ringkas dan terstruktur. Kalau ada
langkah yang gagal/tidak bisa diakses, laporkan apa adanya, jangan ditebak.
```

### Analisa 2 — untuk agent di akun `qolby.mitra@gmail.com` (huffaadz.com, server TUJUAN)

```
Tugas: investigasi read-only hosting ini SEBELUM dipakai sebagai tujuan
migrasi aplikasi Laravel "CATAMU" (domain publik adatamu.id akan diarahkan
ke sini, tanpa mengganggu situs huffaadz.com yang sudah live di akun ini).
JANGAN ubah, hapus, install, atau buat apa pun di langkah ini — murni
pengumpulan fakta.

Akses: SSH u774868168@145.223.108.130 port 65002 (password sudah saya pegang
terpisah), plus hPanel akun qolby.mitra@gmail.com.

Kumpulkan dan laporkan dalam bentuk terstruktur:

1. Konfirmasi akun: pastikan SSH di atas memang terhubung ke hosting akun
   qolby.mitra@gmail.com (bukan akun lain) — cek lewat hPanel bagian SSH
   Access atau sejenisnya.
2. Domain & website yang sudah terdaftar di hPanel akun ini: daftar semua
   domain/subdomain/addon domain yang ada, mana yang sudah ada isinya
   (termasuk situs huffaadz.com yang sudah live — catat docroot-nya persis
   supaya nanti TIDAK disentuh saat setup adatamu.id).
3. Apakah domain **adatamu.id** sudah terdaftar/terlihat di hPanel akun ini
   sama sekali (di menu Domains)? Kalau ya, apakah ada opsi kelola DNS zone
   untuk domain itu di panel ini (bukan cuma linked). Laporkan ada/tidaknya
   dengan jelas — ini menentukan di panel mana nanti A record diubah.
4. IP server sebenarnya akun ini: dari hPanel menu hosting > "Website IP
   address" (jangan pakai dig/nslookup, bisa salah kalau ada CDN di depan).
5. Kapasitas: sisa disk space, apakah ada batas jumlah database MySQL atau
   jumlah website/addon domain di paket ini (dan sudah dipakai berapa dari
   kuotanya).
6. Software di SSH: versi PHP aktif (`php -v`), ada tidaknya `php8.2`,
   `composer --version` (atau konfirmasi composer belum terpasang), `git
   --version`, `mysql --version`.
7. Kalau terlihat ada folder/percobaan setup adatamu.id yang sudah pernah
   dibuat sebagian (dari sesi sebelumnya), laporkan kondisinya apa adanya —
   jangan dilanjutkan atau dihapus, cukup dilaporkan.

Laporkan semua temuan di atas secara ringkas dan terstruktur. Kalau ada
langkah yang gagal/tidak bisa diakses, laporkan apa adanya, jangan ditebak.
```

Setelah dua laporan ini kembali, tempel ke sesi ini supaya Prompt A dan B di
bawah disusun ulang berdasarkan fakta sebenarnya (path docroot, lokasi DNS,
kapasitas hosting baru), bukan asumsi dari dokumen lama.

### Hasil Analisa 1 (zasha.online) — masuk 2026-10-05

**Keterbatasan penting:** agent ini ternyata hanya punya tool read-only level
hPanel (API panel), **bukan shell SSH sungguhan** — tidak bisa `git status`,
`readlink`, `cat .env`, atau query SQL langsung. Jadi hanya fakta yang
kelihatan dari panel yang berhasil dikumpulkan.

Terkonfirmasi (cocok dengan `CATATAN-DEPLOY-ADATAMU.md`):
- IP hosting asli: `37.44.245.146` — sama persis dengan dokumen lama.
- Docroot vhost tercatat di hPanel:
  - `adatamu.id` → `/home/u607709216/domains/adatamu.id/public_html`
  - `admin.adatamu.id` → `.../public_html/admin`
  - `cekin.adatamu.id` → `.../public_html/cekin`
  (ini path vhost yang terdaftar, BUKAN hasil resolusi symlink — isi
  sebenarnya di balik symlink belum diverifikasi)
- MariaDB versi 10.5. PHP aktif di vhost: **PHP 8.3.33** (bukan 8.2 seperti
  disebut di `deploy/update.sh` — perlu diperhitungkan saat deploy ulang).
- PHP 8.2 tersedia sebagai pilihan di hPanel kalau dibutuhkan.

Mengejutkan / perlu diverifikasi ulang:
- `catamu.zasha.online` **tidak muncul sebagai website/vhost terpisah** di
  hPanel sama sekali menurut agent ini. Tidak jelas apakah ini karena memang
  sudah tidak terdaftar, atau karena keterbatasan tool si agent (primary
  domain akun mungkin tidak masuk listing "website" yang sama dengan addon
  domain). **Belum bisa disimpulkan app CATAMU sebenarnya ada di path mana.**

Database yang tercatat di akun (bukan hasil query, cuma listing hPanel):
`u607709216_zasha_v7` (~13MB, terhubung ke zasha.online),
`u607709216_zasha_v5` (~42MB, terhubung ke zasha.online),
`u607709216_bumdes`, `u607709216_kelas`, `u607709216_export` (tidak
terhubung ke vhost manapun), `u607709216_post_multy` (terhubung ke
post.zasha.online). **Tidak ada yang jelas-jelas teridentifikasi sebagai
database CATAMU** — perlu baca `.env` untuk tahu `DB_DATABASE` aslinya.

**Masih belum diketahui (butuh shell SSH sungguhan):** isi `.env` (termasuk
nama database asli, APP_KEY, kredensial Google OAuth), target symlink
sebenarnya, path & ukuran file upload, nama tabel & jumlah baris data
pengguna, versi PHP CLI.

## Prompt A — Deploy ke huffaadz.com (DRAFT, perlu direvisi setelah hasil analisa di atas masuk)

Salin blok di bawah ini apa adanya ke agent AI yang pegang akses hPanel
`qolby.mitra@gmail.com` dan SSH huffaadz.com.

```
Tugas: migrasikan aplikasi Laravel "CATAMU" dari hosting lama (akun Hostinger
muzadidilfuad@gmail.com, domain zasha.online) ke hosting baru ini (akun
Hostinger qolby.mitra@gmail.com, domain huffaadz.com), TANPA mengganggu situs
huffaadz.com yang sudah live di akun ini. Domain publik aplikasi TETAP
adatamu.id (tidak berubah) — yang pindah hanya backend hosting-nya.

SSH tujuan: u774868168@145.223.108.130 port 65002 (password sudah saya
pegang terpisah). Repo aplikasi: https://github.com/muzadidil/catamu
(publik). Baca CATATAN-DEPLOY-ADATAMU.md dan CATATAN-MIGRASI-HUFFAADZ.md di
repo itu untuk konteks lengkap sebelum mulai — jangan ulangi kesalahan yang
sudah pernah ditemukan di sana (IP CDN vs IP asli, docroot subdomain yang
bersarang, SESSION_DOMAIN butuh titik di depan, dll).

Langkah:

0. Verifikasi dulu, jangan asumsi:
   a. Login hPanel qolby.mitra@gmail.com, pastikan SSH di atas memang akun
      hosting huffaadz.com (cocokkan user u774868168).
   b. Cari di mana DNS adatamu.id sekarang benar-benar dikelola (zona DNS-nya,
      bukan tempat domain terdaftar) — cek dulu hPanel qolby.mitra@gmail.com
      (Domains > adatamu.id > DNS), kalau tidak ada di sana baru cek
      Niagahoster qolby.reload@gmail.com. Catat hasilnya.
   c. Cari lokasi docroot situs huffaadz.com yang sudah ada, supaya langkah
      berikutnya tidak menyentuhnya sama sekali.

1. Ambil IP server asli hosting ini dari hPanel (menu hosting > "Website IP
   address"), JANGAN percaya hasil `dig`/`nslookup` kalau Hostinger pakai
   CDN di depannya — bedanya kelihatan dari header `Server: LiteSpeed` vs
   header CDN (`Vary`, `content-security-policy`). Lihat bagian
   "Jangan pakai hasil dig" di CATATAN-DEPLOY-ADATAMU.md kalau perlu contoh.

2. Di hPanel, tambahkan **adatamu.id sebagai website/addon domain baru** di
   akun ini (terpisah dari huffaadz.com). Lalu tambahkan subdomain
   admin.adatamu.id dan cekin.adatamu.id di bawah addon domain itu.
   Kemungkinan besar Hostinger akan menaruh docroot admin/cekin **bersarang
   di dalam** docroot adatamu.id (bukan di domains/<subdomain>/public_html
   terpisah) — ini pernah terjadi di setup lama, cek langsung path aslinya
   setelah dibuat, jangan asumsi.

3. Via SSH, siapkan kode aplikasi di docroot adatamu.id yang baru:
   - Kalau docroot berisi file bawaan Hostinger (bukan git clone): pindahkan
     isinya ke folder `.bak`, lalu `git clone https://github.com/muzadidil/catamu.git .`
   - Symlink docroot admin dan cekin (hasil langkah 2) supaya menunjuk balik
     ke root aplikasi ini — persis pola di CATATAN-DEPLOY-ADATAMU.md bagian
     "Di SSH — arahkan ketiga docroot ke aplikasi yang sama". Masukkan
     `admin` dan `cekin` ke `.gitignore` kalau posisinya di dalam folder git.
   - `composer install --no-dev --optimize-autoloader --no-interaction`

4. Siapkan `.env` baru:
   - Buat database MySQL baru + user di hPanel akun ini.
   - Salin `.env` dari server LAMA (SSH ke u607709216@sg-nme-web502, folder
     ~/domains/catamu.zasha.online/public_html/.env) sebagai acuan.
   - **APP_KEY wajib disalin PERSIS sama** dari `.env` lama — jangan jalankan
     `artisan key:generate`, nanti session/data terenkripsi lama tidak
     terbaca.
   - DB_* diisi kredensial database BARU (langkah di atas).
   - APP_URL, CATAMU_DOMAIN, CATAMU_ADMIN_DOMAIN, CATAMU_CEKIN_DOMAIN,
     SESSION_DOMAIN, kredensial Google OAuth, mail, dll — **disalin sama**
     dari `.env` lama (domain publik tidak berubah, jadi nilainya tetap:
     APP_URL=https://adatamu.id, SESSION_DOMAIN=.adatamu.id dengan titik di
     depan, dst).

5. Migrasikan data dari server lama ke server baru:
   a. Di server lama: `mysqldump` database production-nya ke file .sql.
   b. Pindahkan file .sql itu ke server baru (scp antar server, atau lewat
      mesin perantara), import ke database baru di langkah 4.
   c. Di server lama: kompres folder `storage/app` (dan subfolder lain yang
      dipakai ImageStore/QrisImage untuk simpan upload — cek isi dua file
      itu kalau perlu pastikan path-nya) jadi satu arsip tar/zip.
   d. Pindahkan arsip itu ke server baru, ekstrak ke path `storage/` yang
      sama persis, pastikan permission folder storage tetap writable oleh
      web server (biasanya sama dengan punya aplikasi lama).
   e. `php artisan migrate --force` (aman dijalankan lagi walau DB sudah
      diimpor — hanya menjalankan migration yang belum tercatat).
   f. `php artisan config:clear && php artisan view:clear && php artisan cache:clear`
      (JANGAN `config:cache`/`route:cache` — ada alasan teknisnya di
      deploy/update.sh, routes pakai closure).

6. Verifikasi FUNGSIONAL dulu sebelum DNS publik diubah — jangan ubah DNS di
   langkah ini. Uji lewat curl dengan override Host/IP (`curl --resolve
   adatamu.id:443:<IP-baru> https://adatamu.id/...` atau test via HTTP),
   pastikan: halaman login tampil, login kerja, foto/tanda tangan lama yang
   dipindah bisa diakses, check-in tamu baru bisa disimpan.

7. Kalau langkah 6 semua lolos, baru CUTOVER (lakukan saat traffic sepi,
   karena semua user akan logout saat SESSION_DOMAIN/server pindah):
   a. Di server LAMA: `php artisan down` dulu supaya tidak ada data baru
      masuk selagi cutover (foto/tamu baru) yang nanti hilang karena tidak
      ikut ter-copy.
   b. Ulangi langkah 5a–5d sekali lagi (sinkronisasi akhir) supaya data
      terbaru ikut pindah.
   c. Ubah A record adatamu.id, admin.adatamu.id, cekin.adatamu.id ke IP
      server baru (di panel yang sudah dipastikan di langkah 0b).
   d. Tunggu propagasi DNS, lalu pasang SSL (Let's Encrypt via hPanel) untuk
      ketiga domain itu di akun huffaadz.com.
   e. Uji ulang ketiga alamat via HTTPS sungguhan (bukan curl --resolve lagi).
   f. Kalau semua sehat, BERHENTI DI SINI — laporkan hasilnya. Jangan
      lanjut ke penghapusan server lama (itu Prompt B terpisah, perlu
      konfirmasi tambahan karena destruktif).

Laporkan di setiap langkah besar: apa yang dilakukan, apa hasilnya, dan kalau
ada yang tidak sesuai ekspektasi dokumen (misal path docroot subdomain
berbeda dari dugaan), tulis apa adanya — jangan dipaksakan sesuai dokumen.
```

## Prompt B — Bersih-bersih zasha.online (jalankan SETELAH Prompt A sukses & stabil)

Jangan jalankan ini sebelum Prompt A diverifikasi hidup di `adatamu.id` lewat
DNS sungguhan selama minimal beberapa hari tanpa masalah. Prompt ini
menghapus data permanen di server lama.

```
Tugas: setelah migrasi CATAMU ke hosting huffaadz.com (akun qolby.mitra@gmail.com)
terbukti sukses dan stabil, bersihkan instalasi lama di akun Hostinger
muzadidilfuad@gmail.com (domain zasha.online). SSH: u607709216@sg-nme-web502,
folder ~/domains/catamu.zasha.online/public_html.

Prasyarat sebelum mulai (konfirmasi semua ini benar, kalau ragu BERHENTI dan
tanya user dulu, jangan lanjut):
- adatamu.id, admin.adatamu.id, cekin.adatamu.id sudah resolve ke server
  huffaadz.com dan sudah dipakai user sungguhan tanpa laporan masalah.
- Database dan seluruh isi storage/ (foto tamu, tanda tangan, logo, bukti
  transfer) sudah lengkap tersalin ke server huffaadz.com — bandingkan
  jumlah baris tabel utama dan jumlah file di storage/ antara kedua server
  sebelum menghapus apa pun.

Langkah (urutannya dari yang paling reversibel ke paling destruktif):

1. Hentikan aplikasi lama: `php artisan down` di folder aplikasi (kalau
   belum down dari proses cutover).
2. Hapus symlink yang dibuat waktu setup awal (lihat CATATAN-DEPLOY-ADATAMU.md
   bagian "Di SSH — arahkan ketiga docroot"): hapus symlink `admin` dan
   `cekin` di dalam docroot aplikasi, lalu di folder domain adatamu.id,
   hapus symlink public_html dan kembalikan `public_html.bak` jadi
   `public_html` seperti semula (`mv public_html.bak public_html`).
3. Di hPanel akun muzadidilfuad@gmail.com, lepas/hapus entri subdomain
   admin.adatamu.id dan cekin.adatamu.id serta domain adatamu.id yang dulu
   didaftarkan di sana (karena DNS publik sudah tidak menunjuk ke sini lagi).
   Domain zasha.online dan catamu.zasha.online sendiri JANGAN dihapus/diubah
   kalau masih dipakai untuk hal lain.
4. BARU setelah langkah 1–3 selesai dan tidak ada error: hapus folder
   aplikasi CATAMU (`~/domains/catamu.zasha.online/public_html` beserta
   database MySQL lama di akun ini). Ini destruktif & tidak bisa dibatalkan —
   sebelum menjalankan DROP DATABASE atau `rm -rf`, tampilkan dulu ke user
   apa yang akan dihapus dan tunggu konfirmasi eksplisit "ya hapus", jangan
   jalan otomatis.
5. Keputusan soal paket hosting zasha.online itu sendiri (dibiarkan,
   diturunkan, atau dibatalkan langganannya) di luar scope SSH/hPanel ini —
   laporkan saja ke user, biar mereka yang putuskan di halaman billing
   Hostinger.

Laporkan hasil tiap langkah. Kalau pada langkah 0 (prasyarat) ada yang tidak
bisa dipastikan, STOP dan laporkan ke user alih-alih melanjutkan dengan
asumsi.
```

## Pembagian eksekusi final (disepakati 2026-10-05)

- **Claude (SSH langsung)**: sudah terbukti bisa connect ke kedua server
  (`u607709216@37.44.245.146:65002` dan `u774868168@145.223.108.130:65002`).
  Bagian clone repo, composer install, setup `.env`, migrate — dikerjakan
  Claude langsung, bukan lewat prompt ke agent.
- **Agent AI panel (qolby.mitra@gmail.com)**: kerjakan bagian yang butuh
  browser — tambah website adatamu.id, tambah subdomain admin/cekin, ubah
  DNS, pasang SSL. Pakai Prompt Panel di bawah.
- Masih tahap analisa/persiapan — **belum ada eksekusi nyata**. Checklist
  harus lengkap dulu sebelum Claude menjalankan langkah SSH yang menulis apa
  pun ke server.

## Prompt Panel — untuk agent di akun qolby.mitra@gmail.com (bagian hPanel)

```
Tugas: siapkan tempat (website + subdomain + DNS) di hPanel untuk aplikasi
baru bernama adatamu.id, di akun hosting huffaadz.com ini. JANGAN sentuh
situs huffaadz.com yang sudah ada (docroot: ~/domains/huffaadz.com, symlink
ke ~/huffaadz-app/public) atau subdomain-subdomainnya.

1. Tambahkan adatamu.id sebagai website/addon domain baru di akun ini
   (terpisah dari huffaadz.com). Domain adatamu.id sudah terdaftar di akun
   ini (terlihat di menu Domains), jadi tidak perlu proses tambah-domain-baru
   dari registrar, cukup jadikan sebagai website/hosting baru di panel ini.
   Laporkan path docroot yang dibuat persis (biasanya
   ~/domains/adatamu.id/public_html, tapi konfirmasi apa adanya).
2. Tambahkan subdomain admin.adatamu.id dan cekin.adatamu.id di bawah
   website adatamu.id itu. Laporkan path docroot masing-masing persis
   (kemungkinan bersarang di dalam docroot adatamu.id, konfirmasi apa
   adanya, jangan asumsi).
3. Buat database MySQL baru + user database baru untuk aplikasi ini (nama
   bebas, sarankan sesuatu seperti <prefix>_catamu atau <prefix>_adatamu).
   Catat nama database, nama user, dan host-nya (biasanya localhost).
   PASSWORD database: buat yang kuat, laporkan terpisah dari chat biasa
   kalau platform ini mendukung (atau beri tahu dengan jelas di laporan,
   ini bukan kredensial publik-facing).
4. Di zona DNS adatamu.id (sudah bisa dikelola di panel akun ini): ubah
   record apex (@) dari ALIAS menjadi A record mengarah ke 145.223.108.130.
   Tambahkan A record untuk "admin" dan "cekin" juga mengarah ke IP yang
   sama. Jangan ubah record lain yang tidak terkait (MX, TXT, dll kalau
   ada).
5. Setelah DNS dibuat, coba pasang SSL (Let's Encrypt via hPanel) untuk
   adatamu.id, admin.adatamu.id, cekin.adatamu.id. Kalau gagal karena DNS
   belum propagasi, laporkan saja, bisa dicoba lagi nanti — tidak perlu
   ditunggu di sesi yang sama.

Laporkan ke saya: path docroot sebenarnya (langkah 1 & 2), detail koneksi
database (langkah 3, kredensialnya), status DNS (langkah 4), dan status SSL
(langkah 5). Jangan lakukan apa pun di luar 5 langkah ini.
```

## Yang masih saya butuhkan dari Anda sebelum eksekusi SSH

- `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` — ambil dari Google Cloud
  Console (project yang sama dipakai sebelumnya), tidak bisa diambil dari
  server lama karena foldernya sudah terhapus.
- `SUPER_ADMIN_EMAILS` — email yang akan jadi super admin aplikasi ini.
- (Opsional, bisa nanti) kredensial SMTP kalau mau fitur email jalan dari
  awal — kalau belum ada, saya set `MAIL_MAILER=log` dulu supaya aplikasi
  tetap bisa jalan tanpa email.

## Status eksekusi Prompt Panel — update 2026-10-05

Dijalankan sebagian, dua blocker ditemukan:

- **huffaadz.com aman, tidak tersentuh** (dikonfirmasi agent).
- **Website adatamu.id GAGAL dibuat.** Sistem menandai domain "sudah
  terhubung ke hosting", tapi tidak ketemu sebagai website biasa di akun
  ini, dan agent tidak bisa mengidentifikasi ke mana domain itu sebenarnya
  terhubung. Dugaan: domain pernah ditautkan ke produk Hostinger lain di
  akun yang sama (AI Builder / Website Builder, atau produk lain) —
  **perlu dicek & di-unlink manual oleh user di dashboard Hostinger utama**
  (menu Websites, bukan cuma menu Hosting), baru website biasa bisa dibuat.
- Karena website induk gagal, subdomain admin/cekin, A record, dan SSL
  **belum dicoba** (tergantung langkah 1).
- **Database belum dibuat** — agent butuh "akses Hostinger Agent" yang
  belum aktif di platformnya, perlu diaktifkan user dulu.
- Tidak ada bukti bahwa website adatamu.id yang lama pernah dihapus dari
  akun ini (konsisten — karena memang belum pernah dibuat website di sini,
  domain cuma terdaftar + DNS zone-nya bisa dikelola).

**Menunggu dari user:** (1) konfirmasi/investigasi soal AI Builder atau
produk lain yang mungkin menahan domain adatamu.id, (2) aktifkan akses
"Hostinger Agent" untuk pembuatan database.

## Update — unlink dari zasha.online berhasil (2026-10-05 13:28 UTC)

Penyebab Blocker 1 ketemu: website adatamu.id (beserta subdomain
admin/cekin) ternyata masih tercatat sebagai "website aktif" di akun
**muzadidilfuad@gmail.com** (zasha.online) — walau DNS zone-nya dikelola di
`qolby.mitra@gmail.com`, satu domain tidak bisa "terhubung ke hosting" di
dua akun Hostinger sekaligus. Itu yang menghalangi pembuatan website baru
di akun tujuan.

Sudah dieksekusi (dengan konfirmasi eksplisit user sebelum penghapusan):
- Website adatamu.id + subdomain admin.adatamu.id + cekin.adatamu.id
  **dihapus permanen** dari akun zasha.online (file, DB terkait, FTP,
  redirect ikut terhapus sebagai bagian penghapusan website — DB terkait di
  sini kemungkinan cuma DB kosong bawaan hPanel, bukan DB CATAMU asli yang
  memang sudah lama tidak ada).
- **Dikonfirmasi tidak disentuh**: pendaftaran domain adatamu.id,
  zasha.online, catamu.zasha.online, post.zasha.online, situs/database lain.
- Perlu tunggu beberapa menit untuk propagasi status internal Hostinger
  sebelum retry pemasangan adatamu.id di akun huffaadz.com.

**Langkah selanjutnya:** retry Prompt Panel (bikin website adatamu.id) ke
agent akun `qolby.mitra@gmail.com`, setelah jeda beberapa menit. Blocker 2
(akses "Hostinger Agent" untuk bikin database) masih belum dikonfirmasi
aktif — cek lagi saat retry.

## DEPLOY BERHASIL — live sejak 2026-10-05 ~14:50 UTC

Panel (agent) berhasil: website adatamu.id + subdomain admin/cekin dibuat,
DNS A record ke `145.223.108.130` terpasang, SSL Let's Encrypt aktif.
Database dibuat manual (password dikasih langsung, bukan lewat fitur
auto-generate yang kena Agent credits): `u774868168_adatamu` /
`u774868168_catamu` / host `localhost`.

Bagian SSH dikerjakan langsung oleh Claude (bukan lewat prompt ke agent),
sesuai pembagian kerja yang disepakati:

1. `git clone https://github.com/muzadidil/catamu.git ~/catamu-app` — **sempat
   gagal 401** karena repo sempat ter-private, berhasil setelah user
   mengembalikan ke publik.
2. `composer install --no-dev --optimize-autoloader --no-interaction` —
   **script otomatis `artisan package:discover` gagal** karena `proc_open`
   dinonaktifkan di PHP server ini (umum di shared hosting yang dikeraskan).
   Workaround: `composer install ... --no-scripts`, lalu
   `php artisan package:discover --ansi` manual.
3. `.env` disiapkan dari `.env.example`: `APP_KEY` digenerate baru (bukan
   migrasi data lama, jadi tidak perlu dipertahankan), DB diisi kredensial
   baru, `DB_HOST` harus `localhost` (bukan `127.0.0.1` default
   `.env.example` — user database dibuat scoped ke `localhost`),
   `APP_URL`/`CATAMU_*`/`SESSION_DOMAIN` diisi sesuai adatamu.id.
4. `php artisan migrate --force` — 6 migration jalan bersih (instalasi baru,
   database kosong).
5. `php artisan storage:link` **gagal** karena `exec()` juga dinonaktifkan.
   Workaround: symlink dibuat manual lewat shell
   (`ln -sfn ../storage/app/public public/storage`) — hasilnya identik,
   cuma tidak lewat command Artisan.
6. Docroot: **pola diubah dari dokumen lama** (dulu docroot = root repo).
   Di sini: app code di `~/catamu-app` (code tidak live di docroot),
   `~/domains/adatamu.id/public_html` → symlink ke `~/catamu-app/public`
   (meniru pola huffaadz-app sendiri di akun yang sama — lebih aman, sesuai
   rekomendasi komentar di `.htaccess` repo). Subdomain admin/cekin: dalam
   `catamu-app/public/`, dibuat symlink self-referencing (`admin -> .`,
   `cekin -> .`) supaya kedua subdomain itu juga menyajikan `index.php`
   yang sama (routing domain-based ditangani Laravel sendiri).
7. `config:clear`, `view:clear`, `cache:clear` — normal, tidak ada masalah.
8. **Verifikasi: 200/302/302 dari dalam server (curl --resolve ke
   127.0.0.1) DAN dari luar (DNS publik) — keduanya cocok, DNS sudah
   propagasi penuh lebih cepat dari estimasi 24 jam.**

### Catatan lingkungan penting untuk `deploy/update.sh` ke depannya

Server ini men-disable `proc_open` dan `exec()` di PHP (`disable_functions`).
`deploy/update.sh` yang ada di repo **akan gagal di baris composer install**
kalau dijalankan apa adanya (karena tidak pakai `--no-scripts`). Perlu
disesuaikan untuk akun huffaadz.com ini — baik dengan menambah
`--no-scripts` + jalankan `package:discover` manual, atau minta hosting
mengaktifkan `proc_open`/`exec` (kalau itu bisa diubah user, biasanya via
pilihan "PHP Configuration" di hPanel, belum dicek).

### Masih tertunda (app jalan tapi fitur ini belum aktif)

- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` — login Google (Owner) belum
  bisa dipakai sampai ini diisi.
- `SUPER_ADMIN_EMAILS` — belum ada yang bisa akses sebagai super admin di
  `admin.adatamu.id` sampai ini diisi.
- `MAIL_*` — masih default (`log` driver), email tidak benar-benar terkirim.
- Folder `public_html.bak` (placeholder lama) masih tersimpan di
  `~/domains/adatamu.id/` — boleh dihapus kapan saja setelah dipastikan
  tidak dibutuhkan.

## Bersih-bersih zasha.online — SELESAI (dicek ulang 2026-10-05)

Dicek langsung via SSH setelah penghapusan website lewat hPanel: folder
`~/domains/adatamu.id/` (termasuk `public_html.bak`, `DO_NOT_UPLOAD_HERE`)
**sudah hilang total**. Pencarian `*catamu*`/`*adatamu*` di seluruh home
directory akun `muzadidilfuad@gmail.com` — nihil. Penghapusan website lewat
hPanel ternyata otomatis membereskan filesystem juga, tidak perlu langkah
manual tambahan.

Tersisa cuma verifikasi panel (listing website sinkron, tidak ada tindakan
destruktif lagi). Begitu dikonfirmasi, migrasi CATAMU dari zasha.online ke
huffaadz.com dianggap **tuntas** di kedua sisi.

## Langkah selanjutnya

1. User menyalin **Prompt A** ke agent AI yang pegang akses huffaadz.com,
   jalankan sampai selesai verifikasi fungsional (langkah 1–6), baru
   lanjut cutover DNS (langkah 7) saat traffic sepi.
2. Setelah stabil beberapa hari, baru pakai **Prompt B** untuk bersih-bersih
   zasha.online.
3. Dokumen ini diperbarui kalau ada detail yang ternyata beda di lapangan
   (path docroot, lokasi DNS, dll) supaya jadi rujukan akurat untuk migrasi
   berikutnya.
