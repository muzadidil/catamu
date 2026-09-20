# Catatan Deploy `adatamu.id`

**SELESAI 2026-09-21.** Seluruh langkah 1–6 sudah dikerjakan dan diuji dari luar:

| Alamat | Hasil |
|---|---|
| `https://adatamu.id` | `200`, landing page CATAMU, sertifikat tepercaya |
| `https://adatamu.id/login` | `200` |
| `https://admin.adatamu.id` | `302` → `https://adatamu.id/login` (belum login super admin) |
| `https://cekin.adatamu.id` | `302` → `https://adatamu.id` (sesuai `routes/web.php:91`) |
| `https://catamu.zasha.online` | `302` → `https://adatamu.id/` (rute fallback) |

Bagian di bawah ini disimpan sebagai riwayat: berisi perintah yang benar-benar
dipakai, beserta jebakan yang sempat menyesatkan. Berguna kalau nanti pindah
domain lagi.

## Keputusan: pakai Hostinger, tinggalkan Wasmer

Aplikasi **sudah berjalan** di Hostinger (`catamu.zasha.online`). Yang tersisa hanya
mengarahkan domain `adatamu.id` ke sana — bukan memindahkan aplikasi.

Alasan tidak memakai Wasmer:

- Tab **Storage** kosong ("There is no storage configured here"). CATAMU menyimpan
  foto tamu, tanda tangan, logo kantor, dan bukti transfer di disk lokal
  (`app/Support/ImageStore.php`, `app/Support/QrisImage.php`). Tanpa volume permanen,
  semua file itu hilang setiap instance restart — dari log, restart terjadi tiap ~3 menit.
- SFTP/SSH terkunci paket PRO, jadi migrasi tidak bisa dijalankan manual.
- Akun masih paket `hobby`.
- Server Hostinger ada di Singapura; pengguna aplikasi ini di Indonesia.

Aplikasi Wasmer bernama `adatamu` (owner `muzadidil`) dibiarkan saja, tidak dipakai.

## Kondisi sekarang

| Hal | Nilai |
|---|---|
| Lokasi aplikasi | `~/domains/catamu.zasha.online/public_html` |
| SSH | `u607709216@sg-nme-web502` |
| Panel | https://hpanel.hostinger.com |
| Domain aktif | `catamu.zasha.online` |

## Arsitektur domain yang dituju

| Alamat | Isi |
|---|---|
| `adatamu.id` | Landing page |
| `adatamu.id/login` | Login owner / user / role |
| `admin.adatamu.id` | Backoffice pembayaran langganan (super admin) |
| `cekin.adatamu.id/{nama-kantor}` | Halaman cek-in tamu per perusahaan |

Sudah dicocokkan dengan `routes/web.php` — tidak ada kode yang perlu diubah.

## Langkah yang belum dikerjakan

### 1. Ambil IP server — SUDAH DIKETAHUI

```
37.44.245.146
```

Dibaca dari hPanel → paket Business → Hosting details → "Website IP address",
dan cocok dengan A record apex `zasha.online` beserta seluruh subdomainnya.

**Jangan pakai hasil `dig catamu.zasha.online`.** Perintah itu mengembalikan
`91.108.119.162` dan `153.92.12.180`, yaitu alamat CDN Hostinger yang berdiri di
depan `zasha.online`. Bedanya terlihat dari header respons: hanya `37.44.245.146`
yang menjawab dengan `Server: LiteSpeed`, sedangkan IP CDN menambahkan `Vary` dan
`content-security-policy`. Mengarahkan `adatamu.id` ke IP CDN akan gagal karena
CDN tidak mengenal domain itu.

`2.57.91.91` yang semula terpasang di `adatamu.id` adalah IP halaman parkir
Hostinger — juga bukan server ini.

### 2. Di Niagahoster (`qolby.reload@gmail.com`) — buat A record `adatamu.id`

Member Area → Domain → `adatamu.id` → DNS Management. Pakai A record, **bukan**
ganti nameserver, supaya kendali DNS tetap di satu tempat.

```
A    @        <IP dari langkah 1>
A    admin    <IP dari langkah 1>
A    cekin    <IP dari langkah 1>
```

### 3. Di hPanel — daftarkan domain (butuh verifikasi TXT)

Tambahkan sebagai domain yang **sudah dimiliki** (jangan beli baru):

- `adatamu.id`
- subdomain `admin.adatamu.id`
- subdomain `cekin.adatamu.id`

Karena `adatamu.id` terdaftar di akun Hostinger lain (`qolby.mitra@gmail.com`),
hPanel tidak langsung menerimanya. Yang muncul adalah **verifikasi kepemilikan**:
hPanel memberi satu token TXT, token itu dipasang sebagai record TXT di zona DNS
`adatamu.id` (di akun `qolby.mitra@gmail.com`), tunggu ±10 menit, lalu klik
"Next" di hPanel.

Ini bukan penolakan karena DNS salah arah — jadi jangan diulang-ulang, cukup
selesaikan verifikasinya.

### 4. Di SSH — arahkan ketiga docroot ke aplikasi yang sama

Ketiganya harus menunjuk ke folder yang **sama**, karena Laravel sendiri yang
memilah lewat `Route::domain()`.

**Rencana lama tidak berlaku.** Saat subdomain dibuat lewat hPanel pada
2026-09-20, docroot-nya ternyata **bersarang di dalam** folder domain utama,
bukan folder `domains/<subdomain>/public_html` yang semula diasumsikan:

```
adatamu.id        -> /home/u607709216/domains/adatamu.id/public_html
admin.adatamu.id  -> /home/u607709216/domains/adatamu.id/public_html/admin
cekin.adatamu.id  -> /home/u607709216/domains/adatamu.id/public_html/cekin
```

Akibatnya `admin` dan `cekin` harus berada **di dalam** root aplikasi dan
menunjuk balik ke root aplikasi itu sendiri. Keduanya wajib masuk `.gitignore`,
kalau tidak `deploy/update.sh` akan menolak jalan karena menganggap ada perubahan
di server.

Perintah yang dipakai, dan sudah terbukti jalan:

```bash
APP=/home/u607709216/domains/catamu.zasha.online/public_html
D=/home/u607709216/domains/adatamu.id
mv "$D/public_html" "$D/public_html.bak"
ln -s "$APP" "$D/public_html"
ln -s "$APP" "$APP/admin"
ln -s "$APP" "$APP/cekin"
```

Folder bawaan Hostinger tidak dihapus, hanya disimpan sebagai `public_html.bak`.
Untuk membatalkan: hapus ketiga symlink, lalu `mv "$D/public_html.bak"
"$D/public_html"`.

**Cara menguji.** Jangan pakai HTTP — Hostinger memaksa 301 ke HTTPS dan
jawabannya bisa datang dari cache CDN (`server: hcdn`, header `Age`), sehingga
sempat terbaca `500` padahal aplikasinya sehat. Uji lewat HTTPS, abaikan
sertifikat yang belum ada:

```bash
for h in adatamu.id admin.adatamu.id cekin.adatamu.id; do
  echo "--- $h"; curl -skI "https://$h/?t=$(date +%s)" | grep -iE '^(HTTP/|location|x-powered-by):'
done
```

Selama `.env` belum diubah, jawaban yang benar adalah `302` menuju
`https://catamu.zasha.online/` disertai `x-powered-by: PHP`. Pengalihan itu
berasal dari rute fallback `routes/web.php:172-180` dan justru membuktikan
Laravel terbaca lewat symlink.

### 5. Di hPanel — pasang SSL untuk ketiga domain

### 5b. Di Google Cloud Console — daftarkan URI callback baru

**Wajib dikerjakan sebelum langkah 6.** `AuthController::redirectToGoogle()`
membuat URL callback dari `route('google.callback')`, jadi URL itu ikut berubah
begitu `CATAMU_DOMAIN` diganti. Kalau belum terdaftar, login Owner langsung mati
dengan `redirect_uri_mismatch`.

APIs & Services → Credentials → OAuth 2.0 Client IDs → client aplikasi ini:

```
Authorized redirect URIs   + https://adatamu.id/auth/google/callback
Authorized JavaScript origins + https://adatamu.id
```

Yang lama jangan dihapus, biar `catamu.zasha.online` tetap bisa dipakai kalau
perlu mundur.

### 6. Di SSH — ubah `.env`

```bash
cd ~/domains/catamu.zasha.online/public_html
grep -E 'APP_URL|CATAMU_|SESSION_DOMAIN' .env   # lihat nilai lama dulu
nano .env
```

Nilai yang dituju:

```
APP_URL=https://adatamu.id
CATAMU_DOMAIN=adatamu.id
CATAMU_ADMIN_DOMAIN=admin.adatamu.id
CATAMU_CEKIN_DOMAIN=cekin.adatamu.id
SESSION_DOMAIN=.adatamu.id
```

Titik di depan `.adatamu.id` **wajib**. Halaman login berlogo kantor di
`cekin.adatamu.id/{kantor}/login` mengirim formnya ke rute login di domain utama
(`routes/web.php:105-107`). Tanpa titik itu cookie sesi tidak terbagi antar subdomain
dan login selalu gagal walau password benar.

### 7. Di SSH — bersihkan cache config

```bash
php artisan config:clear
```

## Yang perlu diperhatikan

- **Urutan penting.** Jangan ubah `.env` sebelum langkah 1–5 selesai. Kalau `.env`
  diubah duluan, `catamu.zasha.online` langsung mati sementara `adatamu.id` belum siap.
- **Semua pengguna akan logout** setelah `SESSION_DOMAIN` berubah. Lakukan saat sepi.
- **Link lama tidak mati.** Setelah `CATAMU_DOMAIN` berubah, `catamu.zasha.online`
  otomatis mengalihkan pengunjung ke `adatamu.id` — itu perilaku bawaan
  `routes/web.php:172-180`, tidak perlu diatur manual.
- Kalau setelah langkah 4 muncul **403 / Forbidden**, berarti Hostinger tidak mengikuti
  symlink. Gantinya: pindahkan aplikasi ke folder `adatamu.id`, lalu symlink
  `catamu.zasha.online` balik ke sana.

## Pertanyaan yang sudah terjawab (21 September 2026)

1. **Dua akun terpisah, jangan tertukar:**
   - Hosting + `zasha.online` → hPanel, akun `muzadidilfuad@gmail.com`.
   - Domain `adatamu.id` → akun `qolby.mitra@gmail.com`. Nameserver-nya
     `aster.dns-parking.com` / `helios.dns-parking.com` (DNS bawaan Hostinger),
     jadi A record cukup diatur dari panel akun itu.

   Karena domain dan hosting berada di akun berbeda, pengarahan **wajib** lewat
   A record, bukan lewat penggantian nameserver.
2. **Akses:** semua login dipegang sendiri (panel dan SSH). Akun dipisah hanya untuk
   memisahkan milik klien, bukan karena tidak punya akses.
3. **Kuota website:** masih banyak sisa. Jadi `adatamu.id` cukup ditambahkan sebagai
   website baru; tidak perlu mengganti domain utama `catamu.zasha.online`.

Artinya tidak ada lagi yang menghambat. Biaya tambahan untuk `adatamu.id` juga
**nol** — domain sudah dibeli dan hosting sudah dibayar.

## Cara update aplikasi setelah ada perbaikan

Ini yang membuat Wasmer terasa merepotkan: di sana tidak ada SSH, jadi setiap
perbaikan kecil harus deploy ulang. Di Hostinger cukup satu perintah.

```bash
ssh u607709216@sg-nme-web502
cd ~/domains/catamu.zasha.online/public_html
bash deploy/update.sh
```

`deploy/update.sh` menjalankan: `artisan down` → `git pull` → `composer install`
→ `artisan migrate --force` → bersihkan cache → `artisan up`. Kalau ada file yang
pernah diedit langsung di server, skrip berhenti dan menampilkan daftarnya supaya
perubahan itu tidak tertimpa.

Kalau `php` bawaan SSH bukan 8.2: `PHP_BIN=/usr/bin/php8.2 bash deploy/update.sh`.

### Kalau server belum berupa clone git

Skrip di atas butuh folder aplikasi berupa hasil `git clone`. Kalau dulu di-upload
lewat File Manager/FTP, sekali saja lakukan ini — isi `.env`, database, dan file
unggahan di `storage/` tidak ikut terhapus karena ketiganya di luar git:

```bash
cd ~/domains/catamu.zasha.online/public_html
git init
git remote add origin https://github.com/muzadidil/catamu.git
git fetch origin main
git reset --mixed origin/main   # samakan riwayat tanpa menghapus file yang ada
git checkout -- .               # ambil versi terbaru semua file yang dilacak git
```

Repo `muzadidil/catamu` bersifat publik, jadi `git pull` tidak meminta password.
Kalau nanti dijadikan privat, buat Personal Access Token GitHub dan pakai
`https://<token>@github.com/muzadidil/catamu.git` sebagai remote.

## Catatan teknis

- Commit `95698d9` menambah fallback `env('DB_DATABASE', env('DB_NAME', ...))` di
  `config/database.php`. Itu khusus untuk Wasmer yang menyuntikkan `DB_NAME`.
  Tidak berpengaruh apa pun di Hostinger, jadi aman dibiarkan.
- Kredensial database Wasmer **jangan** ditulis di repo. Kalau Wasmer benar-benar
  ditinggalkan, hapus saja app-nya dari dashboard supaya database ikut terhapus.
