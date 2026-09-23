@php
  $price = number_format($plan['amount'], 0, ',', '.');
  $perMonth = number_format(round($plan['amount'] / 12, -1), 0, ',', '.');
  $cekinHost = config('catamu.domains.cekin');
  $loginUrl = route('login');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#b91c1c" />
  <title>adatamu.id — Buku Tamu Digital untuk Kantor</title>
  <meta name="description" content="adatamu.id mencatat tamu kantor lengkap dengan foto, tanda tangan, dan notifikasi real-time. Cek-in mandiri lewat QR, kelola tim, dan laporan siap cetak. Coba gratis {{ $trialDays }} hari." />
  <meta property="og:title" content="adatamu.id — Buku Tamu Digital untuk Kantor" />
  <meta property="og:description" content="Catat tamu kantor dalam hitungan detik: foto, tanda tangan, cek-in mandiri via QR, dan notifikasi real-time." />
  <meta property="og:type" content="website" />
  <link rel="icon" type="image/png" href="{{ \App\Support\TenantBranding::defaultIconUrl() }}" />
  <link rel="apple-touch-icon" href="{{ \App\Support\TenantBranding::defaultIconUrl() }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" />
  <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}" />
  <script>document.documentElement.classList.add('js');</script>
</head>
<body>
<a class="skip-link" href="#konten">Lewati ke konten</a>

<header class="site-header" id="siteHeader">
  <div class="container header-inner">
    <a class="brand" href="{{ route('landing') }}" aria-label="adatamu.id beranda">
      <span class="brand-mark">AD</span>
      <span class="brand-name">adatamu.id</span>
    </a>
    <nav class="main-nav" id="mainNav" aria-label="Navigasi utama">
      <a href="#fitur">Fitur</a>
      <a href="#cekin">Cek-in Mandiri</a>
      <a href="#cara-kerja">Cara Kerja</a>
      <a href="#harga">Harga</a>
      <a href="#faq">FAQ</a>
      <div class="nav-actions-mobile">
        @if ($dashboardUrl)
        <a class="btn btn-primary btn-block" href="{{ $dashboardUrl }}">Buka Aplikasi</a>
        @else
        <a class="btn btn-ghost btn-block" href="{{ $loginUrl }}">Masuk</a>
        <a class="btn btn-primary btn-block" href="{{ $loginUrl }}">Coba Gratis {{ $trialDays }} Hari</a>
        @endif
      </div>
    </nav>
    <div class="header-actions">
      @if ($dashboardUrl)
      <a class="btn btn-primary" href="{{ $dashboardUrl }}">Buka Aplikasi</a>
      @else
      <a class="btn btn-ghost" href="{{ $loginUrl }}">Masuk</a>
      <a class="btn btn-primary" href="{{ $loginUrl }}">Coba Gratis</a>
      @endif
    </div>
    <button class="nav-toggle" id="navToggle" type="button" aria-controls="mainNav" aria-expanded="false" aria-label="Buka menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<main id="konten">
  {{-- HERO --}}
  <section class="hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container hero-grid">
      <div class="hero-copy reveal">
        <span class="eyebrow"><span class="eyebrow-dot"></span>Buku tamu digital untuk kantor Indonesia</span>
        <h1>Catat setiap tamu kantor dalam <span class="text-accent">hitungan detik.</span></h1>
        <p class="lead">Tinggalkan buku tamu kertas. adatamu.id mencatat identitas, foto, tanda tangan, dan tujuan kunjungan — lalu mengabari tim Anda secara real-time.</p>
        <div class="hero-cta">
          <a class="btn btn-primary btn-lg" href="{{ $dashboardUrl ?? $loginUrl }}">
            {{ $dashboardUrl ? 'Buka Aplikasi' : 'Mulai Trial Gratis' }}
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
          <a class="btn btn-outline btn-lg" href="#cara-kerja">Lihat Cara Kerja</a>
        </div>
        <ul class="hero-points">
          <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Gratis {{ $trialDays }} hari, akses penuh</li>
          <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Masuk cukup dengan akun Google</li>
          <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Bisa dipasang di HP</li>
        </ul>
      </div>

      <div class="hero-visual reveal" aria-hidden="true">
        <div class="mock-browser">
          <div class="mock-bar"><span></span><span></span><span></span><div class="mock-url">{{ $cekinHost }}/kantor-anda/app</div></div>
          <div class="mock-app">
            <div class="mock-side">
              <div class="mock-logo">AD</div>
              <i class="active"></i><i></i><i></i><i></i>
            </div>
            <div class="mock-main">
              <div class="mock-title"><b>Dashboard</b><small>Kamis, 17 September</small></div>
              <div class="mock-stats">
                <div><small>Tamu Hari Ini</small><b>24</b></div>
                <div><small>Sedang Berkunjung</small><b>7</b></div>
                <div><small>Selesai</small><b>17</b></div>
              </div>
              <div class="mock-list">
                <div class="mock-row"><span class="mock-avatar a1">BS</span><div><b>Budi Santoso</b><small>Rapat vendor • Keuangan</small></div><em class="in">Di Dalam</em></div>
                <div class="mock-row"><span class="mock-avatar a2">SA</span><div><b>Siti Aminah</b><small>Wawancara kerja • HRD</small></div><em>Selesai</em></div>
                <div class="mock-row"><span class="mock-avatar a3">AP</span><div><b>Andi Pratama</b><small>Audiensi CSR • Direksi</small></div><em class="in">Di Dalam</em></div>
              </div>
            </div>
          </div>
        </div>

        <div class="mock-phone">
          <div class="mock-phone-notch"></div>
          <div class="mock-phone-screen">
            <div class="mock-phone-head"><span class="brand-mark sm">AD</span><div><b>PT Kantor Anda</b><small>Cek-in Tamu</small></div></div>
            <div class="mock-field"><small>Nama Lengkap</small><span>Rina Wulandari</span></div>
            <div class="mock-field"><small>Bertemu</small><span>Marketing</span></div>
            <div class="mock-field"><small>Keperluan</small><span>Presentasi produk</span></div>
            <div class="mock-sign"><svg viewBox="0 0 120 40"><path d="M6 28c10-4 14-18 22-18s-2 20 6 20 10-14 18-12 2 12 10 12 12-10 20-8 10 6 16 4"/></svg></div>
            <div class="mock-submit">Kirim Cek-in</div>
          </div>
        </div>

        <div class="mock-toast">
          <span class="mock-toast-icon"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg></span>
          <div><b>Tamu baru check-in</b><small>Rina Wulandari • Marketing</small></div>
        </div>
      </div>
    </div>
  </section>

  {{-- AUDIENCE STRIP --}}
  <section class="audience">
    <div class="container">
      <p>Dirancang untuk resepsionis di berbagai tempat</p>
      <ul class="audience-list">
        <li>Kantor &amp; Perusahaan</li>
        <li>Instansi Pemerintah</li>
        <li>Sekolah &amp; Kampus</li>
        <li>Pabrik &amp; Gudang</li>
        <li>Klinik &amp; Rumah Sakit</li>
        <li>Coworking Space</li>
      </ul>
    </div>
  </section>

  {{-- FEATURES --}}
  <section class="section" id="fitur">
    <div class="container">
      <div class="section-head reveal">
        <span class="kicker">Fitur</span>
        <h2>Semua yang dibutuhkan meja resepsionis</h2>
        <p>Dari tamu datang sampai laporan akhir bulan, semuanya tercatat rapi di satu aplikasi.</p>
      </div>
      <div class="feature-grid">
        <article class="feature-card reveal">
          <span class="feature-icon"><svg viewBox="0 0 24 24"><path d="M4 7h4l1.5-2h5L16 7h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="4"/></svg></span>
          <h3>Foto &amp; tanda tangan</h3>
          <p>Ambil foto tamu dengan kamera depan/belakang dan minta tanda tangan langsung di layar.</p>
        </article>
        <article class="feature-card reveal">
          <span class="feature-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/></svg></span>
          <h3>Cek-in mandiri via QR</h3>
          <p>Tamu scan QR di lobi lalu mengisi data sendiri dari HP-nya. Antrean resepsionis jadi lebih singkat.</p>
        </article>
        <article class="feature-card reveal">
          <span class="feature-icon"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg></span>
          <h3>Notifikasi real-time</h3>
          <p>Setiap check-in, check-out, dan perubahan data langsung muncul di aplikasi dan notifikasi HP.</p>
        </article>
        <article class="feature-card reveal">
          <span class="feature-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 20v-1.5A5.5 5.5 0 0 1 9 13h1"/><path d="M15.5 6a2.8 2.8 0 0 1 0 5.3"/><path d="M15 13.3a5 5 0 0 1 5.5 5V20"/></svg></span>
          <h3>Tim &amp; hak akses</h3>
          <p>Tambah Admin, Resepsionis, atau Viewer dengan hak akses berbeda. Semua perangkat tersinkron.</p>
        </article>
        <article class="feature-card reveal">
          <span class="feature-icon"><svg viewBox="0 0 24 24"><path d="M7 8V3h10v5"/><rect x="5" y="14" width="14" height="7" rx="1"/><path d="M5 17H3V10a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7h-2"/></svg></span>
          <h3>Laporan siap cetak</h3>
          <p>Ekspor CSV, cetak daftar tamu, atau bagikan detail kunjungan sebagai PNG 58 mm dan A4.</p>
        </article>
        <article class="feature-card reveal">
          <span class="feature-icon"><svg viewBox="0 0 24 24"><rect x="6" y="2.5" width="12" height="19" rx="2"/><path d="M10 5h4M11 18.5h2"/></svg></span>
          <h3>Terpasang di HP</h3>
          <p>Pasang adatamu.id di layar utama HP atau tablet resepsionis dan gunakan seperti aplikasi biasa.</p>
        </article>
      </div>
    </div>
  </section>

  {{-- SELF CHECK-IN --}}
  <section class="section section-tint" id="cekin">
    <div class="container split">
      <div class="split-copy reveal">
        <span class="kicker">Cek-in Mandiri</span>
        <h2>Satu link khusus untuk setiap kantor</h2>
        <p>Setiap kantor otomatis mendapat halaman cek-in dengan namanya sendiri. Cetak posternya, tempel di lobi, dan biarkan tamu mengisi data dari HP mereka.</p>
        <div class="url-pill">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>
          <span>{{ $cekinHost }}/<b>nama-kantor-anda</b></span>
        </div>
        <ul class="check-list">
          <li><strong>Tanpa instal aplikasi.</strong> Tamu cukup scan QR dan mengisi form.</li>
          <li><strong>Field bisa diatur.</strong> Pilih data yang wajib, opsional, atau disembunyikan.</li>
          <li><strong>Langsung masuk daftar tamu.</strong> Resepsionis menerima notifikasi seketika.</li>
        </ul>
      </div>
      <div class="poster-mock reveal" aria-hidden="true">
        <div class="poster-card">
          <span class="brand-mark">AD</span>
          <b>PT Kantor Anda</b>
          <small>Selamat datang! Silakan cek-in</small>
          <div class="poster-qr">
            @for ($i = 0; $i < 49; $i++)
            <i class="{{ in_array($i, [0,1,2,4,5,6,7,9,11,13,14,15,16,18,20,21,24,26,28,30,32,33,35,36,38,40,41,42,43,45,47,48]) ? 'on' : '' }}"></i>
            @endfor
          </div>
          <span class="poster-url">{{ $cekinHost }}/kantor-anda</span>
        </div>
      </div>
    </div>
  </section>

  {{-- HOW IT WORKS --}}
  <section class="section" id="cara-kerja">
    <div class="container">
      <div class="section-head reveal">
        <span class="kicker">Cara Kerja</span>
        <h2>Siap dipakai dalam 5 menit</h2>
        <p>Tidak perlu instalasi server atau perangkat khusus. Cukup browser di laptop, tablet, atau HP.</p>
      </div>
      <ol class="steps">
        <li class="step reveal">
          <span class="step-num">1</span>
          <h3>Masuk dengan Google</h3>
          <p>Kantor Anda langsung dibuat bersama trial {{ $trialDays }} hari dan daftar departemen awal.</p>
        </li>
        <li class="step reveal">
          <span class="step-num">2</span>
          <h3>Atur kantor &amp; tim</h3>
          <p>Isi nama kantor, departemen tujuan, field tamu, lalu undang resepsionis dengan email atau nomor HP.</p>
        </li>
        <li class="step reveal">
          <span class="step-num">3</span>
          <h3>Terima tamu</h3>
          <p>Catat tamu dari meja resepsionis atau pasang QR cek-in mandiri. Laporan tersusun otomatis.</p>
        </li>
      </ol>
    </div>
  </section>

  {{-- SECURITY --}}
  <section class="section section-dark">
    <div class="container security">
      <div class="security-copy reveal">
        <span class="kicker kicker-light">Keamanan Data</span>
        <h2>Data tamu tersimpan aman dan privat</h2>
        <p>Foto, tanda tangan, dan data kunjungan hanya bisa dibuka oleh anggota kantor yang berhak.</p>
      </div>
      <div class="security-grid">
        <div class="security-item reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><div><b>File privat</b><span>Foto &amp; tanda tangan tidak bisa dibuka tanpa login.</span></div></div>
        <div class="security-item reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/></svg><div><b>Hak akses per role</b><span>Admin, Resepsionis, dan Viewer punya batasan masing-masing.</span></div></div>
        <div class="security-item reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2"/><path d="M10 12h4"/></svg><div><b>Kunci layar dengan PIN</b><span>Perangkat resepsionis aman saat ditinggal sebentar.</span></div></div>
        <div class="security-item reveal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg><div><b>Data terpisah per kantor</b><span>Setiap kantor hanya bisa melihat datanya sendiri.</span></div></div>
      </div>
    </div>
  </section>

  {{-- PRICING --}}
  <section class="section" id="harga">
    <div class="container">
      <div class="section-head reveal">
        <span class="kicker">Harga</span>
        <h2>Satu harga, semua fitur</h2>
        <p>Coba dulu tanpa biaya. Lanjutkan berlangganan hanya jika adatamu.id cocok untuk kantor Anda.</p>
      </div>
      <div class="pricing">
        <div class="price-card reveal">
          <h3>Trial</h3>
          <p class="price-desc">Untuk mencoba seluruh fitur</p>
          <div class="price-value"><span class="price-amount">Gratis</span><span class="price-period">{{ $trialDays }} hari</span></div>
          <ul class="price-features">
            <li>Semua fitur aktif</li>
            <li>Cek-in mandiri &amp; QR</li>
            <li>Tim &amp; hak akses</li>
            <li>Tanpa kartu kredit</li>
          </ul>
          <a class="btn btn-outline btn-block" href="{{ $dashboardUrl ?? $loginUrl }}">Mulai Trial</a>
        </div>
        <div class="price-card featured reveal">
          <span class="price-badge">Paling hemat</span>
          <h3>{{ $plan['name'] }}</h3>
          <p class="price-desc">Untuk operasional kantor sepanjang tahun</p>
          <div class="price-value"><span class="price-amount">Rp{{ $price }}</span><span class="price-period">/ tahun</span></div>
          <p class="price-note">Setara ±Rp{{ $perMonth }} per bulan</p>
          <ul class="price-features">
            <li>Tamu &amp; foto tanpa batas</li>
            <li>Anggota tim tanpa batas</li>
            <li>Laporan CSV, cetak &amp; PNG</li>
            <li>Pembayaran mudah via QRIS</li>
          </ul>
          <a class="btn btn-primary btn-block" href="{{ $dashboardUrl ?? $loginUrl }}">Mulai Sekarang</a>
        </div>
      </div>
    </div>
  </section>

  {{-- FAQ --}}
  <section class="section section-tint" id="faq">
    <div class="container faq-wrap">
      <div class="section-head reveal">
        <span class="kicker">FAQ</span>
        <h2>Pertanyaan yang sering diajukan</h2>
      </div>
      <div class="faq-list">
        <details class="faq-item reveal" open>
          <summary>Apa yang terjadi setelah masa trial berakhir?</summary>
          <p>Data tetap aman dan tetap bisa dilihat, tetapi pencatatan tamu baru dan perubahan data dijeda sampai kantor berlangganan paket {{ $plan['name'] }}.</p>
        </details>
        <details class="faq-item reveal">
          <summary>Bagaimana cara membayar langganan?</summary>
          <p>Buka Pengaturan → Berlangganan, scan QRIS, lalu unggah bukti pembayaran. Masa aktif bertambah setelah pembayaran diverifikasi tim adatamu.id.</p>
        </details>
        <details class="faq-item reveal">
          <summary>Apakah tamu perlu memasang aplikasi untuk cek-in mandiri?</summary>
          <p>Tidak. Tamu cukup scan QR atau membuka link {{ $cekinHost }}/nama-kantor dari browser HP, lalu mengisi form.</p>
        </details>
        <details class="faq-item reveal">
          <summary>Bisakah beberapa resepsionis memakai satu akun kantor?</summary>
          <p>Bisa. Owner dapat menambahkan anggota tim dengan role Admin, Resepsionis, atau Viewer. Setiap anggota masuk memakai email/nomor HP dan password sendiri.</p>
        </details>
        <details class="faq-item reveal">
          <summary>Apakah data bisa diekspor?</summary>
          <p>Bisa. Daftar tamu dapat diekspor ke CSV, dicetak, dan setiap detail kunjungan dapat dibagikan sebagai gambar PNG ukuran 58 mm atau A4.</p>
        </details>
        <details class="faq-item reveal">
          <summary>Apakah bisa dipakai di HP atau tablet?</summary>
          <p>Bisa. Tampilan adatamu.id menyesuaikan layar HP dan tablet, dan dapat dipasang di layar utama seperti aplikasi.</p>
        </details>
      </div>
    </div>
  </section>

  {{-- CTA --}}
  <section class="cta">
    <div class="container cta-inner reveal">
      <div>
        <h2>Siap merapikan buku tamu kantor Anda?</h2>
        <p>Mulai gratis {{ $trialDays }} hari. Kantor Anda siap dalam beberapa menit.</p>
      </div>
      <a class="btn btn-light btn-lg" href="{{ $dashboardUrl ?? $loginUrl }}">{{ $dashboardUrl ? 'Buka Aplikasi' : 'Coba adatamu.id Gratis' }}</a>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="brand" href="{{ route('landing') }}"><span class="brand-mark">AD</span><span class="brand-name">adatamu.id</span></a>
      <p>Buku tamu digital untuk kantor modern. Catat, pantau, dan laporkan kunjungan tamu dengan mudah.</p>
    </div>
    <div class="footer-links">
      <b>Produk</b>
      <a href="#fitur">Fitur</a>
      <a href="#cekin">Cek-in Mandiri</a>
      <a href="#harga">Harga</a>
      <a href="{{ $loginUrl }}">Masuk</a>
    </div>
    <div class="footer-links">
      <b>Legal</b>
      <a href="{{ route('legal.privacy') }}">Kebijakan Privasi</a>
      <a href="{{ route('legal.terms') }}">Syarat &amp; Ketentuan</a>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© {{ now()->year }} adatamu.id. Hak cipta dilindungi.</span>
    <span>Dibuat untuk resepsionis Indonesia.</span>
  </div>
</footer>

<script>
(() => {
  const header = document.getElementById('siteHeader');
  const toggle = document.getElementById('navToggle');
  const nav = document.getElementById('mainNav');

  const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 8);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  const setOpen = open => {
    document.body.classList.toggle('nav-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
  };
  toggle.addEventListener('click', () => setOpen(!document.body.classList.contains('nav-open')));
  nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false)));
  document.addEventListener('keydown', event => { if (event.key === 'Escape') setOpen(false); });

  const items = document.querySelectorAll('.reveal');
  if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    items.forEach(item => item.classList.add('is-visible'));
    return;
  }
  const observer = new IntersectionObserver(entries => entries.forEach(entry => {
    if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
  }), { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
  items.forEach(item => observer.observe(item));
})();
</script>
</body>
</html>
