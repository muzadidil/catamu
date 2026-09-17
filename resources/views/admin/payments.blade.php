@extends('admin.layout')
@php use App\Support\Format; @endphp

@section('title', 'Pembayaran')
@section('page-title', 'Pembayaran')
@section('page-subtitle', 'Verifikasi bukti pembayaran QRIS dari kantor')

@section('content')
<div class="ad-tabs" role="tablist" aria-label="Kategori pembayaran">
  <a class="ad-tab {{ $tab === 'menunggu' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'menunggu' ? 'true' : 'false' }}" href="{{ route('admin.payments') }}">Menunggu verifikasi <span>{{ $pendingCount }}</span></a>
  <a class="ad-tab {{ $tab === 'riwayat' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'riwayat' ? 'true' : 'false' }}" href="{{ route('admin.payments', ['tab' => 'riwayat']) }}">Riwayat</a>
</div>

@if ($tab === 'menunggu')
  @if ($pending->isEmpty())
  <section class="ad-card">
    <div class="ad-empty ad-empty--lg">@include('admin.partials.icon', ['name' => 'check'])<b>Tidak ada pembayaran yang menunggu</b><span>Bukti pembayaran baru dari kantor akan muncul di sini.</span></div>
  </section>
  @else
  <div class="ad-payment-grid">
    @foreach ($pending as $payment)
    @php $tenant = $payment->tenant; @endphp
    <article class="ad-payment">
      <a class="ad-payment-proof" href="{{ route('admin.media.payment', $payment) }}" data-lightbox aria-label="Perbesar bukti pembayaran {{ $tenant->officeName() }}">
        <img src="{{ route('admin.media.payment', $payment) }}" alt="Bukti pembayaran {{ $tenant->officeName() }}" loading="lazy" />
        <span class="ad-payment-zoom">@include('admin.partials.icon', ['name' => 'eye'])Perbesar</span>
      </a>
      <div class="ad-payment-body">
        <div class="ad-payment-top">
          <span class="ad-badge ad-badge--warning">@include('admin.partials.icon', ['name' => 'clock']){{ $payment->created_at->diffForHumans() }}</span>
          <span class="ad-payment-method">{{ $payment->method }}</span>
        </div>
        <a class="ad-payment-office" href="{{ route('admin.tenants.show', $tenant) }}">{{ $tenant->officeName() }}</a>
        <small class="ad-sub">{{ $tenant->owner?->email ?? '-' }}</small>
        <div class="ad-payment-amount">
          <strong class="ad-num">{{ Format::rupiah($payment->amount) }}</strong>
          <span>{{ $payment->plan_name }} • {{ $payment->days }} hari</span>
        </div>
        <dl class="ad-dl ad-dl--compact">
          <div><dt>Status kantor</dt><dd>{{ $tenant->subscriptionLabel() }}</dd></div>
          <div><dt>Berlaku sampai</dt><dd>{{ Format::validUntil($tenant) }}</dd></div>
          <div><dt>Dikirim</dt><dd>{{ Format::date($payment->created_at, 'd M Y, H:i') }}</dd></div>
        </dl>
        <div class="ad-payment-actions">
          <form method="POST" action="{{ route('admin.payments.reject', $payment) }}"
                data-confirm data-confirm-title="Tolak pembayaran {{ $tenant->officeName() }}?"
                data-confirm-message="Masa aktif tidak bertambah. Kantor akan menerima notifikasi beserta alasan penolakan."
                data-confirm-label="Tolak Pembayaran" data-confirm-variant="danger" data-confirm-note="note">
            @csrf
            <input type="hidden" name="note" value="" />
            <button class="ad-btn ad-btn--danger-soft ad-btn--block" type="submit">@include('admin.partials.icon', ['name' => 'x'])<span>Tolak</span></button>
          </form>
          <form method="POST" action="{{ route('admin.payments.approve', $payment) }}"
                data-confirm data-confirm-title="Setujui pembayaran {{ $tenant->officeName() }}?"
                data-confirm-message="Paket {{ $payment->plan_name }} aktif dan masa aktif bertambah {{ $payment->days }} hari. Pastikan nominal {{ Format::rupiah($payment->amount) }} sudah diterima."
                data-confirm-label="Setujui" data-confirm-variant="success">
            @csrf
            <button class="ad-btn ad-btn--success ad-btn--block" type="submit">@include('admin.partials.icon', ['name' => 'check'])<span>Setujui</span></button>
          </form>
        </div>
      </div>
    </article>
    @endforeach
  </div>
  @endif
@else
  <section class="ad-card ad-card--flush">
    @if ($history->isEmpty())
    <div class="ad-empty ad-empty--lg">@include('admin.partials.icon', ['name' => 'receipt'])<b>Belum ada riwayat</b><span>Pembayaran yang sudah disetujui atau ditolak akan tercatat di sini.</span></div>
    @else
    <div class="ad-table-wrap">
      <table class="ad-table">
        <thead><tr><th>Kantor</th><th>Paket</th><th>Nominal</th><th>Status</th><th>Diproses</th><th>Catatan</th></tr></thead>
        <tbody>
        @foreach ($history as $payment)
          <tr>
            <td data-label="Kantor">
              <div class="ad-entity">
                <a class="ad-thumb ad-thumb--sm" href="{{ route('admin.media.payment', $payment) }}" data-lightbox aria-label="Lihat bukti pembayaran"><img src="{{ route('admin.media.payment', $payment) }}" alt="" loading="lazy" /></a>
                <div><a class="ad-entity-name" href="{{ route('admin.tenants.show', $payment->tenant) }}">{{ $payment->tenant->officeName() }}</a><small>{{ $payment->tenant->owner?->email ?? '-' }}</small></div>
              </div>
            </td>
            <td data-label="Paket"><span class="ad-text">{{ $payment->plan_name }}</span></td>
            <td data-label="Nominal"><span class="ad-text ad-num">{{ Format::rupiah($payment->amount) }}</span></td>
            <td data-label="Status">@if ($payment->status === 'approved')<span class="ad-badge ad-badge--success">Disetujui</span>@else<span class="ad-badge ad-badge--danger">Ditolak</span>@endif</td>
            <td data-label="Diproses"><span class="ad-text">{{ Format::date($payment->reviewed_at, 'd M Y, H:i') }}</span><small class="ad-sub">{{ $payment->reviewer?->name ?? '-' }}</small></td>
            <td data-label="Catatan"><span class="ad-text">{{ $payment->review_note ?: '-' }}</span></td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    @include('admin.pagination', ['paginator' => $history])
    @endif
  </section>
@endif
@endsection
