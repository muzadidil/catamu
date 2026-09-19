@extends('admin.layout')
@php use App\Support\Format; @endphp

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan Platform')
@section('page-subtitle', 'Atur masa trial, pembayaran QRIS, dan informasi paket')

@section('content')
<div class="ad-grid-2 ad-grid-2--settings">
  <section class="ad-card">
    <header class="ad-card-head">
      <div><h2>Masa trial</h2><p>Berlaku untuk kantor yang mendaftar setelah perubahan disimpan</p></div>
      <span class="ad-card-icon tone-warning">@include('admin.partials.icon', ['name' => 'sparkle'])</span>
    </header>
    <form method="POST" action="{{ route('admin.settings.trial') }}" class="ad-form">
      @csrf
      <label class="ad-field" for="trialDays">
        <span class="ad-field-label">Lama trial kantor baru</span>
        <span class="ad-input-group">
          <input id="trialDays" name="trial_days" type="number" min="1" max="365" inputmode="numeric" value="{{ old('trial_days', $trialDays) }}" required data-trial-input />
          <span>hari</span>
        </span>
        <span class="ad-field-hint">Antara 1–365 hari. Kantor yang sedang trial tidak berubah.</span>
      </label>
      <div class="ad-presets" aria-label="Pilihan cepat">
        @foreach ([3, 7, 14, 30] as $preset)
        <button class="ad-chip ad-chip--sm {{ (int) old('trial_days', $trialDays) === $preset ? 'is-active' : '' }}" type="button" data-trial-preset="{{ $preset }}">{{ $preset }} hari</button>
        @endforeach
      </div>
      <div class="ad-preview">
        @include('admin.partials.icon', ['name' => 'calendar'])
        <span>Kantor yang mendaftar hari ini mendapat trial sampai <b data-trial-preview data-today="{{ now()->toDateString() }}">{{ now()->addDays($trialDays)->translatedFormat('d F Y') }}</b>.</span>
      </div>
      <div class="ad-form-actions"><button class="ad-btn ad-btn--primary" type="submit">Simpan Masa Trial</button></div>
    </form>
  </section>

  <section class="ad-card">
    <header class="ad-card-head">
      <div><h2>Gambar QRIS</h2><p>Tampil di modal pembayaran seluruh kantor</p></div>
      <span class="ad-card-icon tone-brand">@include('admin.partials.icon', ['name' => 'qr'])</span>
    </header>
    <form method="POST" action="{{ route('admin.settings.qris') }}" enctype="multipart/form-data" class="ad-form">
      @csrf
      <label class="ad-dropzone {{ $qrisUrl ? 'has-image' : '' }}" for="qrisFile" data-dropzone>
        <img src="{{ $qrisUrl }}" alt="QRIS saat ini" data-dropzone-preview @unless ($qrisUrl) hidden @endunless />
        <span class="ad-dropzone-empty" data-dropzone-empty @if ($qrisUrl) hidden @endif>
          @include('admin.partials.icon', ['name' => 'upload'])
          <b>Pilih gambar QRIS merchant</b>
          <small>PNG, JPG, atau WEBP • maks. 2 MB</small>
        </span>
        <input id="qrisFile" name="qris" type="file" accept="image/png,image/jpeg,image/webp" required />
      </label>
      <p class="ad-field-hint">Disarankan PNG agar kode tetap tajam saat dipindai. Tanpa gambar, kantor melihat pesan "QRIS Belum Tersedia".</p>
      <div class="ad-form-actions"><button class="ad-btn ad-btn--primary" type="submit">{{ $qrisUrl ? 'Ganti Gambar QRIS' : 'Simpan Gambar QRIS' }}</button></div>
    </form>
  </section>
</div>

<section class="ad-card">
  <header class="ad-card-head">
    <div><h2>Program afiliasi</h2><p>Komisi untuk kantor yang mengajak kantor lain berlangganan</p></div>
    <span class="ad-card-icon tone-brand">@include('admin.partials.icon', ['name' => 'link'])</span>
  </header>
  <form method="POST" action="{{ route('admin.settings.affiliate') }}" class="ad-form">
    @csrf
    <div class="ad-grid-2">
      <label class="ad-field" for="affiliateRate">
        <span class="ad-field-label">Komisi per pembayaran</span>
        <span class="ad-input-group">
          <input id="affiliateRate" name="affiliate_rate" type="number" min="0" max="100" inputmode="numeric" value="{{ old('affiliate_rate', $affiliateRate) }}" required />
          <span>%</span>
        </span>
        <span class="ad-field-hint">Saat ini {{ $affiliateRate }}% dari {{ Format::rupiah($plan['amount']) }} = {{ Format::rupiah((int) floor($plan['amount'] * $affiliateRate / 100)) }} untuk tiap kantor yang diajak.</span>
      </label>
      <label class="ad-field" for="affiliateMinPayout">
        <span class="ad-field-label">Minimum pencairan</span>
        <span class="ad-input-group">
          <input id="affiliateMinPayout" name="affiliate_min_payout" type="number" min="0" step="1000" inputmode="numeric" value="{{ old('affiliate_min_payout', $affiliateMinPayout) }}" required />
          <span>rupiah</span>
        </span>
        <span class="ad-field-hint">Owner baru dapat mengajukan pencairan setelah saldonya mencapai nilai ini.</span>
      </label>
    </div>
    <p class="ad-field-hint">Perubahan berlaku untuk komisi yang tercatat setelah disimpan. Komisi lama tetap memakai persentase yang berlaku saat itu.</p>
    <div class="ad-form-actions"><button class="ad-btn ad-btn--primary" type="submit">Simpan Pengaturan Afiliasi</button></div>
  </form>
</section>

<section class="ad-card">
  <header class="ad-card-head">
    <div><h2>Masukan &amp; Rating</h2><p>Saran, laporan masalah, dan penilaian yang dikirim kantor</p></div>
    <span class="ad-card-icon tone-brand">@include('admin.partials.icon', ['name' => 'chat'])</span>
  </header>
  <div class="ad-form-actions"><a class="ad-btn ad-btn--primary" href="{{ route('admin.feedbacks') }}">Buka Masukan &amp; Rating</a></div>
</section>

<section class="ad-card">
  <header class="ad-card-head"><div><h2>Paket langganan</h2><p>Paket yang tampil di aplikasi kantor dan landing page</p></div></header>
  <div class="ad-plan">
    <div class="ad-plan-main">
      <span class="ad-badge ad-badge--accent">Paket aktif</span>
      <h3>{{ $plan['name'] }}</h3>
      <p>{{ $plan['days'] }} hari masa aktif setelah pembayaran disetujui</p>
    </div>
    <strong class="ad-plan-price ad-num">{{ Format::rupiah($plan['amount']) }}</strong>
  </div>
  <p class="ad-field-hint ad-plan-hint">Harga paket diatur di <code>config/catamu.php</code> agar sama dengan harga di halaman Berlangganan kantor.</p>
</section>
@endsection
