@extends('admin.layout')
@php use App\Support\Format; @endphp

@section('title', 'Ringkasan')
@section('page-title', 'Ringkasan')
@section('page-subtitle', 'Kondisi seluruh kantor adatamu.id per '.now()->translatedFormat('l, d F Y'))
@section('page-actions')
  <a class="ad-btn ad-btn--primary" href="{{ route('admin.payments') }}">@include('admin.partials.icon', ['name' => 'card'])<span>Verifikasi Pembayaran</span>@if ($stats['pending'])<em class="ad-btn-count">{{ $stats['pending'] }}</em>@endif</a>
@endsection

@section('content')
<section class="ad-kpis" aria-label="Angka utama">
  <a class="ad-kpi" href="{{ route('admin.tenants') }}">
    <span class="ad-kpi-icon">@include('admin.partials.icon', ['name' => 'building'])</span>
    <span class="ad-kpi-label">Total kantor</span>
    <strong class="ad-kpi-value">{{ Format::number($stats['tenants']) }}</strong>
    <span class="ad-kpi-note">+{{ Format::number($stats['newThisMonth']) }} bulan ini</span>
  </a>
  <a class="ad-kpi" href="{{ route('admin.tenants', ['status' => 'aktif']) }}">
    <span class="ad-kpi-icon tone-success">@include('admin.partials.icon', ['name' => 'check'])</span>
    <span class="ad-kpi-label">Langganan aktif</span>
    <strong class="ad-kpi-value">{{ Format::number($stats['active']) }}</strong>
    <span class="ad-kpi-note">{{ Format::number($stats['lifetime']) }} kantor Lifetime</span>
  </a>
  <a class="ad-kpi" href="{{ route('admin.tenants', ['status' => 'trial']) }}">
    <span class="ad-kpi-icon tone-warning">@include('admin.partials.icon', ['name' => 'sparkle'])</span>
    <span class="ad-kpi-label">Sedang trial</span>
    <strong class="ad-kpi-value">{{ Format::number($stats['trial']) }}</strong>
    <span class="ad-kpi-note">{{ Format::number($stats['expired']) }} kantor kedaluwarsa</span>
  </a>
  <a class="ad-kpi {{ $stats['pending'] ? 'is-attention' : '' }}" href="{{ route('admin.payments') }}">
    <span class="ad-kpi-icon tone-brand">@include('admin.partials.icon', ['name' => 'clock'])</span>
    <span class="ad-kpi-label">Menunggu verifikasi</span>
    <strong class="ad-kpi-value">{{ Format::number($stats['pending']) }}</strong>
    <span class="ad-kpi-note">{{ $stats['pending'] ? 'Perlu ditinjau' : 'Semua sudah diproses' }}</span>
  </a>
  <div class="ad-kpi">
    <span class="ad-kpi-icon tone-accent">@include('admin.partials.icon', ['name' => 'wallet'])</span>
    <span class="ad-kpi-label">Pendapatan bulan ini</span>
    <strong class="ad-kpi-value" title="{{ Format::rupiah($stats['revenueMonth']) }}">{{ Format::rupiahCompact($stats['revenueMonth']) }}</strong>
    <span class="ad-kpi-note">Total {{ Format::rupiahCompact($stats['revenueTotal']) }}</span>
  </div>
  <div class="ad-kpi">
    <span class="ad-kpi-icon">@include('admin.partials.icon', ['name' => 'users'])</span>
    <span class="ad-kpi-label">Tamu hari ini</span>
    <strong class="ad-kpi-value">{{ Format::number($stats['guestsToday']) }}</strong>
    <span class="ad-kpi-note">{{ Format::number($stats['selfCheckinsToday']) }} lewat cek-in mandiri</span>
  </div>
</section>

<div class="ad-grid-2">
  <section class="ad-card">
    <header class="ad-card-head">
      <div><h2>Perlu diverifikasi</h2><p>Bukti pembayaran yang paling lama menunggu</p></div>
      <a class="ad-link" href="{{ route('admin.payments') }}">Lihat semua @include('admin.partials.icon', ['name' => 'arrow-right'])</a>
    </header>
    @forelse ($pendingPayments as $payment)
    <div class="ad-list-row">
      <a class="ad-thumb" href="{{ route('admin.media.payment', $payment) }}" data-lightbox aria-label="Lihat bukti pembayaran {{ $payment->tenant->officeName() }}"><img src="{{ route('admin.media.payment', $payment) }}" alt="" loading="lazy" /></a>
      <div class="ad-list-main">
        <b>{{ $payment->tenant->officeName() }}</b>
        <small>{{ $payment->plan_name }} • {{ Format::rupiah($payment->amount) }}</small>
      </div>
      <span class="ad-list-meta" title="{{ Format::date($payment->created_at, 'd M Y, H:i') }}">{{ $payment->created_at->diffForHumans() }}</span>
    </div>
    @empty
    <div class="ad-empty">@include('admin.partials.icon', ['name' => 'check'])<b>Tidak ada antrean</b><span>Semua bukti pembayaran sudah diproses.</span></div>
    @endforelse
  </section>

  <section class="ad-card">
    <header class="ad-card-head">
      <div><h2>Trial segera berakhir</h2><p>Berakhir dalam 3 hari ke depan — peluang untuk di-follow up</p></div>
      <a class="ad-link" href="{{ route('admin.tenants', ['status' => 'trial']) }}">Semua trial @include('admin.partials.icon', ['name' => 'arrow-right'])</a>
    </header>
    @forelse ($trialEnding as $tenant)
    <a class="ad-list-row is-link" href="{{ route('admin.tenants.show', $tenant) }}">
      <span class="ad-avatar ad-avatar--soft">{{ Format::initials($tenant->officeName()) }}</span>
      <div class="ad-list-main">
        <b>{{ $tenant->officeName() }}</b>
        <small>{{ $tenant->owner?->email ?? '-' }}</small>
      </div>
      <span class="ad-badge ad-badge--warning">{{ $tenant->trial_expires_at->diffForHumans(['parts' => 1]) }}</span>
    </a>
    @empty
    <div class="ad-empty">@include('admin.partials.icon', ['name' => 'calendar'])<b>Tidak ada trial yang segera berakhir</b><span>Kantor trial akan muncul di sini 3 hari sebelum berakhir.</span></div>
    @endforelse
  </section>
</div>

<section class="ad-card">
  <header class="ad-card-head">
    <div><h2>Kantor terbaru</h2><p>Pendaftaran paling baru</p></div>
    <a class="ad-link" href="{{ route('admin.tenants') }}">Kelola kantor @include('admin.partials.icon', ['name' => 'arrow-right'])</a>
  </header>
  @if ($latestTenants->isEmpty())
  <div class="ad-empty">@include('admin.partials.icon', ['name' => 'building'])<b>Belum ada kantor</b><span>Kantor yang mendaftar dengan Google akan muncul di sini.</span></div>
  @else
  <div class="ad-table-wrap">
    <table class="ad-table">
      <thead><tr><th>Kantor</th><th>Owner</th><th>Status</th><th>Terdaftar</th><th class="ad-col-actions"><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
      @foreach ($latestTenants as $tenant)
        <tr>
          <td data-label="Kantor"><div class="ad-entity"><span class="ad-avatar ad-avatar--soft">{{ Format::initials($tenant->officeName()) }}</span><div><b>{{ $tenant->officeName() }}</b><small>/{{ $tenant->slug }}</small></div></div></td>
          <td data-label="Owner"><b class="ad-text">{{ $tenant->owner?->name ?? '-' }}</b><small class="ad-sub">{{ $tenant->owner?->email ?? '-' }}</small></td>
          <td data-label="Status">@include('admin.partials.status', ['tenant' => $tenant])</td>
          <td data-label="Terdaftar"><span class="ad-text">{{ Format::date($tenant->created_at) }}</span></td>
          <td class="ad-col-actions"><a class="ad-btn ad-btn--pill ad-btn--neutral ad-btn--sm" href="{{ route('admin.tenants.show', $tenant) }}">@include('admin.partials.icon', ['name' => 'eye'])<span>Detail</span></a></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</section>
@endsection
