@extends('legal.layout')

@section('title', 'Syarat & Ketentuan • adatamu.id')
@section('heading', 'Syarat & Ketentuan')

@section('legal')
{{-- Draf ringkas berdasarkan fitur aplikasi. Tinjau bersama penasihat hukum sebelum dipublikasikan. --}}
<p>Dengan menggunakan adatamu.id, kantor dan anggota timnya menyetujui ketentuan berikut.</p>

<h3>Akun dan akses</h3>
<ul>
  <li>Owner masuk menggunakan akun Google dan bertanggung jawab atas seluruh aktivitas di akun kantornya.</li>
  <li>Owner mengelola anggota tim, role, dan hak akses. Password dan PIN wajib dijaga kerahasiaannya.</li>
</ul>

<h3>Langganan</h3>
<ul>
  <li>Kantor baru mendapatkan masa trial {{ \App\Models\PlatformSetting::trialDays() }} hari.</li>
  <li>Setelah trial atau langganan berakhir, data tetap dapat dilihat tetapi perubahan diblokir sampai paket aktif kembali.</li>
  <li>Pembayaran QRIS baru menambah masa aktif setelah bukti pembayaran disetujui admin adatamu.id.</li>
</ul>

<h3>Tanggung jawab pengguna</h3>
<p>Kantor wajib mencatat data tamu secara sah dan memberi tahu tamu tentang pencatatan data, termasuk foto dan tanda tangan.</p>

<h3>Layanan PWA</h3>
<p>adatamu.id dapat dipasang di HP sebagai aplikasi (PWA). Koneksi internet tetap diperlukan untuk menyimpan dan menampilkan data.</p>

<h3>Penghapusan data</h3>
<p>Penghapusan akun kantor bersifat permanen dan tidak dapat dibatalkan, termasuk seluruh data tamu, tim, dan riwayat pembayaran.</p>
@endsection
