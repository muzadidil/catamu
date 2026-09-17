@extends('admin.layout')
@php use App\Support\Format; @endphp

@section('title', 'Kantor')
@section('page-title', 'Kantor')
@section('page-subtitle', 'Cari, pantau status langganan, dan kelola paket setiap kantor')

@section('content')
<form class="ad-search" method="GET" action="{{ route('admin.tenants') }}" role="search">
  @include('admin.partials.icon', ['name' => 'search'])
  <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama kantor, email owner, slug, atau ID kantor" aria-label="Cari kantor" />
  @if ($filter !== 'semua')<input type="hidden" name="status" value="{{ $filter }}" />@endif
  @if ($search !== '')
  <a class="ad-search-clear" href="{{ route('admin.tenants', array_filter(['status' => $filter !== 'semua' ? $filter : null])) }}" aria-label="Hapus pencarian">@include('admin.partials.icon', ['name' => 'x'])</a>
  @endif
  <button class="ad-btn ad-btn--primary ad-btn--sm" type="submit">Cari</button>
</form>

<div class="ad-chips" role="tablist" aria-label="Filter status">
  @foreach ($filters as $key => $item)
  <a class="ad-chip {{ $filter === $key ? 'is-active' : '' }}" role="tab" aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
     href="{{ route('admin.tenants', array_filter(['status' => $key !== 'semua' ? $key : null, 'q' => $search !== '' ? $search : null])) }}">
    {{ $item['label'] }} <span>({{ Format::number($counts[$key]) }})</span>
  </a>
  @endforeach
</div>

<section class="ad-card ad-card--flush">
  @if ($tenants->isEmpty())
  <div class="ad-empty ad-empty--lg">
    @include('admin.partials.icon', ['name' => 'search'])
    <b>{{ $search !== '' ? 'Kantor tidak ditemukan' : 'Belum ada kantor di kategori ini' }}</b>
    <span>{{ $search !== '' ? 'Coba kata kunci lain atau ubah filter status.' : 'Kantor akan muncul setelah owner mendaftar dengan akun Google.' }}</span>
  </div>
  @else
  <div class="ad-table-wrap">
    <table class="ad-table ad-table--tenants">
      <thead>
        <tr><th>Kantor</th><th>Owner</th><th>Paket &amp; Status</th><th>Pemakaian</th><th class="ad-col-actions">Aksi Super Admin</th></tr>
      </thead>
      <tbody>
      @foreach ($tenants as $tenant)
        <tr>
          <td data-label="Kantor">
            <div class="ad-entity">
              <span class="ad-avatar ad-avatar--soft">{{ Format::initials($tenant->officeName()) }}</span>
              <div>
                <a class="ad-entity-name" href="{{ route('admin.tenants.show', $tenant) }}">{{ $tenant->officeName() }}</a>
                <small>ID {{ $tenant->id }} • <a href="{{ $tenant->checkinUrl() }}" target="_blank" rel="noopener">/{{ $tenant->slug }}</a></small>
              </div>
            </div>
          </td>
          <td data-label="Owner"><b class="ad-text">{{ $tenant->owner?->name ?? '-' }}</b><small class="ad-sub">{{ $tenant->owner?->email ?? '-' }}</small></td>
          <td data-label="Paket & Status"><div class="ad-badges">@include('admin.partials.status', ['tenant' => $tenant])</div><small class="ad-sub">{{ $tenant->lifetime ? 'Lifetime • tanpa batas waktu' : $tenant->plan.' • s/d '.Format::validUntil($tenant) }}</small></td>
          <td data-label="Pemakaian"><span class="ad-text">{{ Format::number($tenant->guests_count) }} tamu</span><small class="ad-sub">{{ Format::number($tenant->team_members_count) }} anggota tim</small></td>
          <td class="ad-col-actions">@include('admin.partials.tenant-actions', ['tenant' => $tenant])</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @include('admin.pagination', ['paginator' => $tenants])
  @endif
</section>
@endsection
