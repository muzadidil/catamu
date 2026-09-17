@extends('admin.layout')
@php use App\Support\Format; $name = $tenant->officeName(); @endphp

@section('title', $name)
@section('back', route('admin.tenants'))
@section('page-title', $name)
@section('page-subtitle', 'Terdaftar '.Format::date($tenant->created_at, 'd F Y').' • ID kantor '.$tenant->id)

@section('content')
<section class="ad-card ad-hero-card">
  <div class="ad-hero-main">
    <span class="ad-avatar ad-avatar--lg">{{ Format::initials($name) }}</span>
    <div class="ad-hero-text">
      <div class="ad-badges">@include('admin.partials.status', ['tenant' => $tenant])</div>
      <h2>{{ $name }}</h2>
      <div class="ad-hero-links">
        <a href="{{ $tenant->checkinUrl() }}" target="_blank" rel="noopener">@include('admin.partials.icon', ['name' => 'qr'])<span>{{ preg_replace('#^https?://#', '', $tenant->checkinUrl()) }}</span></a>
        @if ($settings['address'] !== 'Alamat kantor belum diatur')
        <span>@include('admin.partials.icon', ['name' => 'building'])<span>{{ $settings['address'] }}</span></span>
        @endif
      </div>
    </div>
  </div>
  @include('admin.partials.tenant-actions', ['tenant' => $tenant, 'hideDetail' => true, 'class' => 'ad-actions--hero'])
</section>

<section class="ad-kpis ad-kpis--compact" aria-label="Pemakaian kantor">
  <div class="ad-kpi"><span class="ad-kpi-label">Total tamu</span><strong class="ad-kpi-value">{{ Format::number($tenant->guests_count) }}</strong><span class="ad-kpi-note">Terakhir {{ $usage['lastGuestAt'] ? \Illuminate\Support\Carbon::parse($usage['lastGuestAt'])->diffForHumans() : 'belum ada' }}</span></div>
  <div class="ad-kpi"><span class="ad-kpi-label">Tamu bulan ini</span><strong class="ad-kpi-value">{{ Format::number($usage['guestsMonth']) }}</strong><span class="ad-kpi-note">{{ Format::number($usage['guestsToday']) }} hari ini</span></div>
  <div class="ad-kpi"><span class="ad-kpi-label">Cek-in mandiri</span><strong class="ad-kpi-value">{{ Format::number($usage['selfCheckins']) }}</strong><span class="ad-kpi-note">Lewat halaman QR</span></div>
  <div class="ad-kpi"><span class="ad-kpi-label">Anggota tim</span><strong class="ad-kpi-value">{{ Format::number($tenant->teamMembers->count()) }}</strong><span class="ad-kpi-note">{{ Format::number($tenant->departments_count) }} departemen</span></div>
</section>

<div class="ad-grid-2">
  <section class="ad-card">
    <header class="ad-card-head"><div><h2>Langganan</h2><p>Paket dan masa aktif saat ini</p></div></header>
    <dl class="ad-dl">
      <div><dt>Paket</dt><dd>{{ $tenant->plan }}</dd></div>
      <div><dt>Status</dt><dd><div class="ad-badges">@include('admin.partials.status', ['tenant' => $tenant])</div></dd></div>
      <div><dt>Berlaku sampai</dt><dd>{{ Format::validUntil($tenant) }}</dd></div>
      <div><dt>Trial</dt><dd>{{ Format::date($tenant->trial_started_at) }} – {{ Format::date($tenant->trial_expires_at) }}</dd></div>
      <div><dt>Pertama aktif</dt><dd>{{ Format::date($tenant->activated_at, 'd M Y, H:i') }}</dd></div>
      <div><dt>Terakhir disetujui</dt><dd>{{ Format::date($tenant->approved_at, 'd M Y, H:i') }}</dd></div>
    </dl>
  </section>

  <section class="ad-card">
    <header class="ad-card-head"><div><h2>Owner &amp; kontak kantor</h2><p>Akun Google pemilik dan kontak publik</p></div></header>
    <dl class="ad-dl">
      <div><dt>Owner</dt><dd>{{ $tenant->owner?->name ?? '-' }}</dd></div>
      <div><dt>Email owner</dt><dd>@if ($tenant->owner?->email)<a class="ad-link" href="mailto:{{ $tenant->owner->email }}">{{ $tenant->owner->email }}</a>@else - @endif</dd></div>
      <div><dt>Login terakhir</dt><dd>{{ $tenant->owner?->last_login_at?->diffForHumans() ?? '-' }}</dd></div>
      <div><dt>Telepon / WA</dt><dd>{{ $settings['phone'] ?: '-' }}</dd></div>
      <div><dt>Email kantor</dt><dd>{{ $settings['email'] ?: '-' }}</dd></div>
      <div><dt>Jam operasional</dt><dd>{{ $settings['hours'] }}</dd></div>
    </dl>
  </section>
</div>

<section class="ad-card ad-card--flush">
  <header class="ad-card-head"><div><h2>Riwayat pembayaran</h2><p>{{ $tenant->payments->count() }} pembayaran tercatat</p></div></header>
  @if ($tenant->payments->isEmpty())
  <div class="ad-empty">@include('admin.partials.icon', ['name' => 'receipt'])<b>Belum ada pembayaran</b><span>Bukti pembayaran QRIS dari kantor ini akan muncul di sini.</span></div>
  @else
  <div class="ad-table-wrap">
    <table class="ad-table">
      <thead><tr><th>Bukti</th><th>Paket</th><th>Nominal</th><th>Status</th><th>Dikirim</th><th>Diproses</th></tr></thead>
      <tbody>
      @foreach ($tenant->payments->sortByDesc('id') as $payment)
        <tr>
          <td data-label="Bukti"><a class="ad-thumb ad-thumb--sm" href="{{ route('admin.media.payment', $payment) }}" data-lightbox aria-label="Lihat bukti pembayaran"><img src="{{ route('admin.media.payment', $payment) }}" alt="" loading="lazy" /></a></td>
          <td data-label="Paket"><span class="ad-text">{{ $payment->plan_name }}</span></td>
          <td data-label="Nominal"><span class="ad-text ad-num">{{ Format::rupiah($payment->amount) }}</span></td>
          <td data-label="Status">
            @if ($payment->status === 'approved')<span class="ad-badge ad-badge--success">Disetujui</span>
            @elseif ($payment->status === 'rejected')<span class="ad-badge ad-badge--danger">Ditolak</span>
            @else<span class="ad-badge ad-badge--warning">Menunggu</span>@endif
            @if ($payment->review_note)<small class="ad-sub">{{ $payment->review_note }}</small>@endif
          </td>
          <td data-label="Dikirim"><span class="ad-text">{{ Format::date($payment->created_at, 'd M Y, H:i') }}</span></td>
          <td data-label="Diproses"><span class="ad-text">{{ Format::date($payment->reviewed_at, 'd M Y, H:i') }}</span><small class="ad-sub">{{ $payment->reviewer?->name }}</small></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</section>

<div class="ad-grid-2">
  <section class="ad-card">
    <header class="ad-card-head"><div><h2>Anggota tim</h2><p>{{ $tenant->teamMembers->count() }} anggota</p></div></header>
    @forelse ($tenant->teamMembers as $member)
    <div class="ad-list-row">
      <span class="ad-avatar ad-avatar--soft">{{ Format::initials($member->name) }}</span>
      <div class="ad-list-main"><b>{{ $member->name }}</b><small>{{ collect([$member->email, $member->phone])->filter()->implode(' • ') ?: 'Kontak belum diatur' }}</small></div>
      <div class="ad-badges"><span class="ad-badge ad-badge--neutral">{{ $member->role }}</span>@unless ($member->active)<span class="ad-badge ad-badge--danger">Nonaktif</span>@endunless</div>
    </div>
    @empty
    <div class="ad-empty">@include('admin.partials.icon', ['name' => 'users'])<b>Belum ada anggota tim</b><span>Owner dapat menambahkan anggota dari Pengaturan → Kelola Tim.</span></div>
    @endforelse
  </section>

  <section class="ad-card">
    <header class="ad-card-head">
      <div><h2>Masukan &amp; rating</h2><p>{{ $tenant->rating_score ? 'Rating '.$tenant->rating_score.'/5' : 'Belum memberi rating' }}</p></div>
      @if ($tenant->rating_score)<span class="ad-stars" aria-label="{{ $tenant->rating_score }} dari 5 bintang">{{ str_repeat('★', $tenant->rating_score) }}<i>{{ str_repeat('★', 5 - $tenant->rating_score) }}</i></span>@endif
    </header>
    @if ($tenant->rating_comment)
    <blockquote class="ad-quote">{{ $tenant->rating_comment }}</blockquote>
    @endif
    @forelse ($tenant->feedbacks->sortByDesc('id')->take(5) as $feedback)
    <div class="ad-feedback">
      <div class="ad-feedback-head"><span class="ad-badge ad-badge--neutral">{{ $feedback->category }}</span><small>{{ $feedback->created_at->diffForHumans() }}</small></div>
      <b>{{ $feedback->title }}</b>
      <p>{{ $feedback->message }}</p>
    </div>
    @empty
    @unless ($tenant->rating_comment)
    <div class="ad-empty">@include('admin.partials.icon', ['name' => 'chat'])<b>Belum ada masukan</b><span>Masukan dari kantor ini akan muncul di sini.</span></div>
    @endunless
    @endforelse
  </section>
</div>
@endsection
