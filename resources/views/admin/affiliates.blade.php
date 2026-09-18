@extends('admin.layout')
@php use App\Support\Format; @endphp

@section('title', 'Afiliasi')
@section('page-title', 'Afiliasi')
@section('page-subtitle', 'Komisi antar kantor dan pencairan saldo afiliasi')

@section('content')
<section class="ad-kpis ad-kpis--compact" aria-label="Angka program afiliasi">
  <div class="ad-kpi">
    <span class="ad-kpi-icon tone-brand">@include('admin.partials.icon', ['name' => 'link'])</span>
    <span class="ad-kpi-label">Kantor dari referral</span>
    <strong class="ad-kpi-value">{{ Format::number($totals['referredOffices']) }}</strong>
    <span class="ad-kpi-note">Mendaftar lewat link undangan</span>
  </div>
  <div class="ad-kpi">
    <span class="ad-kpi-icon tone-accent">@include('admin.partials.icon', ['name' => 'wallet'])</span>
    <span class="ad-kpi-label">Total komisi</span>
    <strong class="ad-kpi-value" title="{{ Format::rupiah($totals['commission']) }}">{{ Format::rupiahCompact($totals['commission']) }}</strong>
    <span class="ad-kpi-note">Komisi berjalan {{ $rate }}% per pembayaran</span>
  </div>
  <div class="ad-kpi {{ $pendingCount ? 'is-attention' : '' }}">
    <span class="ad-kpi-icon tone-warning">@include('admin.partials.icon', ['name' => 'clock'])</span>
    <span class="ad-kpi-label">Menunggu dicairkan</span>
    <strong class="ad-kpi-value" title="{{ Format::rupiah($totals['onHold']) }}">{{ Format::rupiahCompact($totals['onHold']) }}</strong>
    <span class="ad-kpi-note">{{ $pendingCount ? $pendingCount.' pengajuan perlu ditinjau' : 'Semua sudah diproses' }}</span>
  </div>
  <div class="ad-kpi">
    <span class="ad-kpi-icon tone-success">@include('admin.partials.icon', ['name' => 'check'])</span>
    <span class="ad-kpi-label">Sudah dibayarkan</span>
    <strong class="ad-kpi-value" title="{{ Format::rupiah($totals['paid']) }}">{{ Format::rupiahCompact($totals['paid']) }}</strong>
    <span class="ad-kpi-note">Pencairan yang sudah dikirim</span>
  </div>
</section>

<div class="ad-tabs" role="tablist" aria-label="Kategori pencairan">
  <a class="ad-tab {{ $tab === 'menunggu' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'menunggu' ? 'true' : 'false' }}" href="{{ route('admin.affiliates') }}">Menunggu pencairan <span>{{ $pendingCount }}</span></a>
  <a class="ad-tab {{ $tab === 'riwayat' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'riwayat' ? 'true' : 'false' }}" href="{{ route('admin.affiliates', ['tab' => 'riwayat']) }}">Riwayat</a>
</div>

@if ($tab === 'menunggu')
  @if ($pending->isEmpty())
  <section class="ad-card">
    <div class="ad-empty ad-empty--lg">@include('admin.partials.icon', ['name' => 'check'])<b>Tidak ada pengajuan pencairan</b><span>Pengajuan komisi dari kantor akan muncul di sini.</span></div>
  </section>
  @else
  <section class="ad-card ad-card--flush">
    <div class="ad-table-wrap">
      <table class="ad-table">
        <thead><tr><th>Kantor</th><th>Nominal</th><th>Rekening tujuan</th><th>Diajukan</th><th>Tindakan</th></tr></thead>
        <tbody>
        @foreach ($pending as $payout)
          <tr>
            <td data-label="Kantor">
              <div><a class="ad-entity-name" href="{{ route('admin.tenants.show', $payout->tenant) }}">{{ $payout->tenant->officeName() }}</a><small>{{ $payout->tenant->owner?->email ?? '-' }}</small></div>
            </td>
            <td data-label="Nominal"><strong class="ad-text ad-num">{{ Format::rupiah($payout->amount) }}</strong></td>
            <td data-label="Rekening tujuan"><span class="ad-text">{{ $payout->bank }} {{ $payout->account }}</span><small class="ad-sub">a.n. {{ $payout->account_name }}</small></td>
            <td data-label="Diajukan"><span class="ad-text">{{ Format::date($payout->created_at, 'd M Y, H:i') }}</span></td>
            <td data-label="Tindakan">
              <div class="ad-actions">
                <form method="POST" action="{{ route('admin.affiliates.reject', $payout) }}"
                      data-confirm data-confirm-title="Tolak pencairan {{ $payout->tenant->officeName() }}?"
                      data-confirm-message="Saldo komisi kembali tersedia dan kantor dapat mengajukan ulang."
                      data-confirm-label="Tolak" data-confirm-variant="danger" data-confirm-note="note">
                  @csrf
                  <input type="hidden" name="note" value="" />
                  <button class="ad-btn ad-btn--danger-soft" type="submit">@include('admin.partials.icon', ['name' => 'x'])<span>Tolak</span></button>
                </form>
                <form method="POST" action="{{ route('admin.affiliates.approve', $payout) }}"
                      data-confirm data-confirm-title="Tandai {{ Format::rupiah($payout->amount) }} sudah dikirim?"
                      data-confirm-message="Pastikan transfer ke {{ $payout->bank }} {{ $payout->account }} a.n. {{ $payout->account_name }} benar-benar sudah dilakukan."
                      data-confirm-label="Sudah Dikirim" data-confirm-variant="success">
                  @csrf
                  <button class="ad-btn ad-btn--success" type="submit">@include('admin.partials.icon', ['name' => 'check'])<span>Sudah Dikirim</span></button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </section>
  @endif
@else
  <section class="ad-card ad-card--flush">
    @if ($history->isEmpty())
    <div class="ad-empty ad-empty--lg">@include('admin.partials.icon', ['name' => 'receipt'])<b>Belum ada riwayat</b><span>Pencairan yang sudah disetujui atau ditolak akan tercatat di sini.</span></div>
    @else
    <div class="ad-table-wrap">
      <table class="ad-table">
        <thead><tr><th>Kantor</th><th>Nominal</th><th>Rekening</th><th>Status</th><th>Diproses</th><th>Catatan</th></tr></thead>
        <tbody>
        @foreach ($history as $payout)
          <tr>
            <td data-label="Kantor"><div><a class="ad-entity-name" href="{{ route('admin.tenants.show', $payout->tenant) }}">{{ $payout->tenant->officeName() }}</a><small>{{ $payout->tenant->owner?->email ?? '-' }}</small></div></td>
            <td data-label="Nominal"><span class="ad-text ad-num">{{ Format::rupiah($payout->amount) }}</span></td>
            <td data-label="Rekening"><span class="ad-text">{{ $payout->bank }} {{ $payout->account }}</span><small class="ad-sub">a.n. {{ $payout->account_name }}</small></td>
            <td data-label="Status">@if ($payout->status === 'approved')<span class="ad-badge ad-badge--success">Dicairkan</span>@else<span class="ad-badge ad-badge--danger">Ditolak</span>@endif</td>
            <td data-label="Diproses"><span class="ad-text">{{ Format::date($payout->reviewed_at, 'd M Y, H:i') }}</span><small class="ad-sub">{{ $payout->reviewer?->name ?? '-' }}</small></td>
            <td data-label="Catatan"><span class="ad-text">{{ $payout->review_note ?: '-' }}</span></td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
    @include('admin.pagination', ['paginator' => $history])
    @endif
  </section>
@endif

<section class="ad-card ad-card--flush">
  <header class="ad-card-head"><div><h2>Kantor pengajak teratas</h2><p>Berdasarkan total komisi yang sudah didapat</p></div></header>
  @if ($topReferrers->isEmpty())
  <div class="ad-empty">@include('admin.partials.icon', ['name' => 'users'])<b>Belum ada komisi tercatat</b><span>Komisi muncul setelah kantor yang diajak membayar langganan.</span></div>
  @else
  <div class="ad-table-wrap">
    <table class="ad-table">
      <thead><tr><th>Kantor</th><th>Jumlah komisi</th><th>Total didapat</th></tr></thead>
      <tbody>
      @foreach ($topReferrers as $row)
        <tr>
          <td data-label="Kantor">
            @if ($row->referrer)
            <div><a class="ad-entity-name" href="{{ route('admin.tenants.show', $row->referrer) }}">{{ $row->referrer->officeName() }}</a><small>{{ $row->referrer->owner?->email ?? '-' }}</small></div>
            @else
            <span class="ad-text">Kantor sudah dihapus</span>
            @endif
          </td>
          <td data-label="Jumlah komisi"><span class="ad-text">{{ $row->entries }}x</span></td>
          <td data-label="Total didapat"><strong class="ad-text ad-num">{{ Format::rupiah($row->total) }}</strong></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif
</section>
@endsection
