@php
  $admin = auth()->user();
  $nav = [
    ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Ringkasan', 'short' => 'Ringkasan', 'icon' => 'home'],
    ['route' => 'admin.payments', 'match' => 'admin.payments*', 'label' => 'Pembayaran', 'short' => 'Bayar', 'icon' => 'card', 'badge' => $pendingPaymentsCount],
    ['route' => 'admin.tenants', 'match' => 'admin.tenants*', 'label' => 'Kantor', 'short' => 'Kantor', 'icon' => 'building'],
    ['route' => 'admin.affiliates', 'match' => 'admin.affiliates*', 'label' => 'Afiliasi', 'short' => 'Afiliasi', 'icon' => 'link', 'badge' => $pendingPayoutsCount],
    // Masukan & Rating sengaja tidak di menu: pintunya ada di halaman Pengaturan
    // supaya menu bawah mobile tidak melimpah ke baris kedua.
    ['route' => 'admin.settings', 'match' => ['admin.settings*', 'admin.feedbacks'], 'label' => 'Pengaturan', 'short' => 'Setelan', 'icon' => 'settings'],
  ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#b91c1c" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="robots" content="noindex" />
  @include('admin.partials.theme-init')
  <title>@yield('title') • Backoffice adatamu.id</title>
  <link rel="icon" type="image/png" href="{{ asset('icon-192.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" />
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}" />
</head>
<body>
<div class="ad-shell">
  <aside class="ad-sidebar" id="adSidebar">
    <a class="ad-brand" href="{{ route('admin.dashboard') }}">
      <span class="ad-brand-mark">AD</span>
      <span class="ad-brand-text"><b>adatamu.id</b><small>Backoffice</small></span>
    </a>

    <nav class="ad-nav" aria-label="Menu backoffice">
      <p class="ad-nav-label">Menu</p>
      @foreach ($nav as $item)
      <a class="ad-nav-link {{ request()->routeIs($item['match']) ? 'is-active' : '' }}" href="{{ route($item['route']) }}" @if (request()->routeIs($item['match'])) aria-current="page" @endif>
        @include('admin.partials.icon', ['name' => $item['icon']])
        <span>{{ $item['label'] }}</span>
        @if (! empty($item['badge']))
        <em class="ad-nav-badge">{{ $item['badge'] }}</em>
        @endif
      </a>
      @endforeach
    </nav>

    <div class="ad-sidebar-card">
      <p>Situs publik</p>
      <a href="{{ route('landing') }}" target="_blank" rel="noopener">{{ config('catamu.domains.main') }} @include('admin.partials.icon', ['name' => 'external'])</a>
    </div>

    <div class="ad-sidebar-foot">
      <div class="ad-user">
        <span class="ad-avatar">{{ \App\Support\Format::initials($admin->name) }}</span>
        <div class="ad-user-text"><b>{{ $admin->name }}</b><small>{{ $admin->email }}</small></div>
      </div>
      <form method="POST" action="{{ route('logout') }}" data-confirm data-confirm-title="Keluar dari backoffice?" data-confirm-message="Anda perlu masuk kembali dengan akun Google super admin." data-confirm-label="Keluar" data-confirm-variant="danger">
        @csrf
        <button class="ad-icon-btn" type="submit" title="Keluar" aria-label="Keluar">@include('admin.partials.icon', ['name' => 'logout'])</button>
      </form>
    </div>
  </aside>

  <div class="ad-main">
    <header class="ad-topbar">
      <div class="ad-topbar-title">
        @hasSection('back')
        <a class="ad-back" href="@yield('back')">@include('admin.partials.icon', ['name' => 'arrow-left'])<span>Kembali</span></a>
        @endif
        <h1>@yield('page-title')</h1>
        <p>@yield('page-subtitle')</p>
      </div>
      <div class="ad-topbar-actions">
        @yield('page-actions')
        <div class="ad-topbar-tools">
          <form class="ad-topbar-logout" method="POST" action="{{ route('logout') }}" data-confirm data-confirm-title="Keluar dari backoffice?" data-confirm-message="Anda perlu masuk kembali dengan akun Google super admin." data-confirm-label="Keluar" data-confirm-variant="danger">
            @csrf
            <button class="ad-theme-btn ad-logout-btn" type="submit" title="Keluar" aria-label="Keluar">@include('admin.partials.icon', ['name' => 'logout'])</button>
          </form>
          <button class="ad-theme-btn" type="button" data-theme-toggle title="Gunakan tema gelap" aria-label="Gunakan tema gelap">
            @include('admin.partials.icon', ['name' => 'moon', 'class' => 'ad-icon--moon'])
            @include('admin.partials.icon', ['name' => 'sun', 'class' => 'ad-icon--sun'])
          </button>
        </div>
      </div>
    </header>

    <main class="ad-content">
      @yield('content')
    </main>
  </div>
</div>

<nav class="ad-bottom-nav" aria-label="Menu backoffice mobile">
  @foreach ($nav as $item)
  <a class="{{ request()->routeIs($item['match']) ? 'is-active' : '' }}" href="{{ route($item['route']) }}">
    <span class="ad-bottom-icon">@include('admin.partials.icon', ['name' => $item['icon']])@if (! empty($item['badge']))<em>{{ $item['badge'] }}</em>@endif</span>
    <span>{{ $item['short'] }}</span>
  </a>
  @endforeach
</nav>

<dialog class="ad-dialog" id="adConfirm" aria-labelledby="adConfirmTitle">
  <form method="dialog" class="ad-dialog-panel">
    <div class="ad-dialog-icon" id="adConfirmIcon">@include('admin.partials.icon', ['name' => 'alert'])</div>
    <h2 id="adConfirmTitle">Konfirmasi</h2>
    <p id="adConfirmMessage"></p>
    <label class="ad-dialog-field" id="adConfirmNoteWrap" hidden>
      <span>Alasan penolakan <em>(opsional, dikirim ke kantor)</em></span>
      <textarea id="adConfirmNote" rows="3" maxlength="500" placeholder="Contoh: nominal transfer tidak sesuai"></textarea>
    </label>
    <label class="ad-dialog-field" id="adConfirmTypeWrap" hidden>
      <span>Ketik <code id="adConfirmTypeWord">HAPUS</code> untuk melanjutkan</span>
      <input id="adConfirmType" autocomplete="off" spellcheck="false" />
    </label>
    <div class="ad-dialog-actions">
      <button class="ad-btn ad-btn--primary" id="adConfirmOk" value="ok" type="submit">Lanjutkan</button>
      <button class="ad-btn ad-btn--neutral" value="cancel" type="submit" formnovalidate>Batal</button>
    </div>
  </form>
</dialog>

<dialog class="ad-lightbox" id="adLightbox" aria-label="Pratinjau gambar">
  <button class="ad-lightbox-close" type="button" data-lightbox-close aria-label="Tutup">@include('admin.partials.icon', ['name' => 'x'])</button>
  <img id="adLightboxImage" alt="" />
  <a class="ad-btn ad-btn--neutral" id="adLightboxOpen" href="#" target="_blank" rel="noopener">Buka ukuran penuh @include('admin.partials.icon', ['name' => 'external'])</a>
</dialog>

<div class="ad-toast" id="adToast" role="status" aria-live="polite">{{ session('toast') ?? ($errors->any() ? $errors->first() : '') }}</div>

<script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
</body>
</html>
