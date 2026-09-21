@extends('admin.layout')
@php
  use App\Support\Format;
  $maxCount = max(1, $distribution->max());
@endphp

@section('title', 'Masukan & Rating')
@section('page-title', 'Masukan & Rating')
@section('page-subtitle', 'Suara pengguna untuk pengembangan adatamu.id')

@section('content')
<div class="ad-grid-rating">
  <section class="ad-card ad-rating-summary">
    <span class="ad-kpi-label">Rata-rata rating</span>
    <strong class="ad-hero-figure">{{ $averageRating ? number_format($averageRating, 1, ',', '.') : '–' }}</strong>
    <span class="ad-stars ad-stars--lg" aria-hidden="true">{{ str_repeat('★', (int) round($averageRating ?? 0)) }}<i>{{ str_repeat('★', 5 - (int) round($averageRating ?? 0)) }}</i></span>
    <span class="ad-kpi-note">dari {{ Format::number($ratingCount) }} kantor</span>
  </section>

  <section class="ad-card">
    <header class="ad-card-head"><div><h2>Sebaran rating</h2><p>Jumlah kantor per nilai bintang</p></div></header>
    <div class="ad-bars" role="list">
      @foreach ($distribution as $score => $total)
      <div class="ad-bar-row" role="listitem" tabindex="0" title="{{ $score }} bintang: {{ $total }} kantor" aria-label="{{ $score }} bintang: {{ $total }} kantor">
        <span class="ad-bar-label">{{ $score }} ★</span>
        <span class="ad-bar-track"><span class="ad-bar-fill" style="width: {{ $total ? max(2, round($total / $maxCount * 100, 1)) : 0 }}%"></span></span>
        <span class="ad-bar-value">{{ Format::number($total) }}</span>
      </div>
      @endforeach
    </div>
  </section>
</div>

@if ($ratings->isNotEmpty())
<section class="ad-card">
  <header class="ad-card-head"><div><h2>Rating terbaru</h2><p>Komentar dari kantor yang memberi rating</p></div></header>
  <div class="ad-review-grid">
    @foreach ($ratings as $tenant)
    <article class="ad-review">
      <div class="ad-review-head">
        <span class="ad-avatar ad-avatar--soft">{{ Format::initials($tenant->officeName()) }}</span>
        <div><a class="ad-entity-name" href="{{ route('admin.tenants.show', $tenant) }}">{{ $tenant->officeName() }}</a><small class="ad-sub">{{ $tenant->rating_updated_at?->diffForHumans() }}</small></div>
        <span class="ad-stars" aria-label="{{ $tenant->rating_score }} dari 5 bintang">{{ str_repeat('★', $tenant->rating_score) }}<i>{{ str_repeat('★', 5 - $tenant->rating_score) }}</i></span>
      </div>
      <p>{{ $tenant->rating_comment ?: 'Tanpa komentar.' }}</p>
    </article>
    @endforeach
  </div>
</section>
@endif

<div class="ad-chips" role="tablist" aria-label="Kategori masukan">
  <a class="ad-chip {{ ! $category ? 'is-active' : '' }}" href="{{ route('admin.feedbacks') }}">Semua <span>({{ Format::number($categoryCounts->sum()) }})</span></a>
  @foreach ($categories as $item)
  <a class="ad-chip {{ $category === $item ? 'is-active' : '' }}" href="{{ route('admin.feedbacks', ['kategori' => $item]) }}">{{ $item }} <span>({{ Format::number($categoryCounts[$item] ?? 0) }})</span></a>
  @endforeach
</div>

<section class="ad-card ad-card--flush">
  @forelse ($feedbacks as $feedback)
  <article class="ad-feedback ad-feedback--row">
    <div class="ad-feedback-head">
      <span class="ad-badge ad-badge--{{ ['Masalah' => 'danger', 'Fitur' => 'accent', 'Saran' => 'success'][$feedback->category] ?? 'neutral' }}">{{ $feedback->category }}</span>
      @if ($feedback->tenant)<a class="ad-link" href="{{ route('admin.tenants.show', $feedback->tenant) }}">{{ $feedback->tenant->officeName() }}</a>@endif
      <small>{{ $feedback->user?->name ?? '-' }} • {{ Format::date($feedback->created_at, 'd M Y, H:i') }}</small>
    </div>
    <b>{{ $feedback->title }}</b>
    <p>{{ $feedback->message }}</p>
  </article>
  @empty
  <div class="ad-empty ad-empty--lg">@include('admin.partials.icon', ['name' => 'chat'])<b>Belum ada masukan</b><span>Masukan yang dikirim kantor dari menu Pengaturan → Masukan akan muncul di sini.</span></div>
  @endforelse
  @include('admin.pagination', ['paginator' => $feedbacks])
</section>
@endsection
