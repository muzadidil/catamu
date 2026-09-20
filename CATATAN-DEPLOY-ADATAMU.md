# Catatan Deploy `adatamu.id`

Status per 2026-09-20. Lanjutkan dari bagian "Langkah yang belum dikerjakan".

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

### 1. Ambil IP server (via SSH)

```bash
dig +short catamu.zasha.online
```

### 2. Di registrar `adatamu.id` — buat A record

Pakai A record, **bukan** nameserver, supaya kendali DNS tetap di akun sendiri
dan tidak berpindah ke akun zasha.online.

```
A    @        <IP dari langkah 1>
A    admin    <IP dari langkah 1>
A    cekin    <IP dari langkah 1>
```

### 3. Di hPanel zasha.online — daftarkan domain

Tambahkan sebagai domain yang **sudah dimiliki** (jangan beli baru):

- `adatamu.id`
- subdomain `admin.adatamu.id`
- subdomain `cekin.adatamu.id`

### 4. Di SSH — symlink ketiganya ke aplikasi yang sudah ada

Penting: ketiganya harus menunjuk ke folder yang **sama**. Jangan buat folder terpisah,
karena Laravel sendiri yang memilah lewat `Route::domain()`.

```bash
cd ~/domains
APP=~/domains/catamu.zasha.online/public_html
for d in adatamu.id admin.adatamu.id cekin.adatamu.id; do
  mv "$d/public_html" "$d/public_html.bak"
  ln -s "$APP" "$d/public_html"
done
ls -la adatamu.id admin.adatamu.id cekin.adatamu.id
```

### 5. Di hPanel — pasang SSL untuk ketiga domain

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

## Pertanyaan yang belum terjawab

1. `adatamu.id` dibeli di registrar mana? (menentukan letak menu DNS-nya)
2. Punya akses login hPanel zasha.online, atau hanya SSH? Mendaftarkan domain
   **harus** lewat hPanel, tidak bisa dari SSH.
3. Kuota website paket Hostinger — sudah terpakai berapa dari berapa? Kalau penuh,
   alternatifnya bukan menambah website baru, tapi **mengganti domain utama** website
   `catamu.zasha.online` menjadi `adatamu.id` (hemat satu slot).

## Catatan teknis

- Commit `95698d9` menambah fallback `env('DB_DATABASE', env('DB_NAME', ...))` di
  `config/database.php`. Itu khusus untuk Wasmer yang menyuntikkan `DB_NAME`.
  Tidak berpengaruh apa pun di Hostinger, jadi aman dibiarkan.
- Kredensial database Wasmer **jangan** ditulis di repo. Kalau Wasmer benar-benar
  ditinggalkan, hapus saja app-nya dari dashboard supaya database ikut terhapus.
