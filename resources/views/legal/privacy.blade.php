@extends('legal.layout')

@section('title', 'Kebijakan Privasi • CATAMU')
@section('heading', 'Kebijakan Privasi')

@section('legal')
{{-- Draf ringkas berdasarkan fitur aplikasi. Tinjau bersama penasihat hukum sebelum dipublikasikan. --}}
<p>CATAMU adalah aplikasi buku tamu digital untuk kantor. Halaman ini menjelaskan data yang diproses saat kantor dan anggota timnya menggunakan CATAMU.</p>

<h3>Data yang diproses</h3>
<ul>
  <li>Akun Owner: nama, alamat email, dan ID akun Google yang dipakai untuk masuk.</li>
  <li>Anggota tim: nama, email, nomor HP, role, dan password (disimpan dalam bentuk hash).</li>
  <li>Data tamu: nama, nomor HP, instansi, email, plat nomor, tujuan kunjungan, catatan, foto, tanda tangan, serta waktu check-in dan check-out.</li>
  <li>Data kantor: pengaturan aplikasi, departemen, notifikasi, masukan, rating, status langganan, dan bukti pembayaran.</li>
</ul>

<h3>Tujuan penggunaan</h3>
<p>Data digunakan untuk menjalankan fitur buku tamu, mengatur hak akses tim, memverifikasi pembayaran langganan, dan meningkatkan layanan berdasarkan masukan pengguna.</p>

<h3>Penyimpanan dan keamanan</h3>
<p>Data disimpan di server CATAMU dan hanya dapat diakses oleh anggota kantor yang memiliki hak akses. Foto, tanda tangan, dan bukti pembayaran disimpan sebagai file privat yang tidak dapat dibuka tanpa login.</p>

<h3>Retensi dan penghapusan</h3>
<p>Data disimpan selama akun kantor aktif. Owner dapat menghapus data tamu atau menghapus akun kantor beserta seluruh datanya melalui menu Pengaturan → Akun.</p>

<h3>Hak pengguna</h3>
<p>Kantor bertanggung jawab atas data tamu yang dicatat. Tamu dapat meminta kantor terkait untuk melihat, memperbaiki, atau menghapus datanya.</p>
@endsection
