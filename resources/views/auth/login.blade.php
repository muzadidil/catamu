@extends('layouts.catamu')

@section('title', 'Masuk • CATAMU')

@section('body')
@php
  $mode = old('mode', 'owner');
@endphp
<div class="app-lock show" id="appLock" aria-hidden="false">
  <section class="auth-visual" aria-hidden="true">
    <div class="auth-brand-top">
      <span class="auth-brand-logo">C</span>
      <div class="auth-brand-copy">
        <strong>CATAMU</strong>
        <span>Buku Tamu Digital</span>
      </div>
    </div>

    <div class="auth-slides" id="authSlides">
      <article class="auth-slide auth-slide-1 active">
        <div class="auth-slide-content">
          <span class="auth-eyebrow"><i></i>Buku Tamu Digital</span>
          <h1>Kelola Tamu Kantor Lebih Rapi</h1>
          <p>Tinggalkan buku tamu kertas. Semua kunjungan tercatat otomatis dan bisa dipantau kapan saja.</p>
        </div>
      </article>
      <article class="auth-slide auth-slide-2">
        <div class="auth-slide-content">
          <span class="auth-eyebrow"><i></i>Ketertiban &amp; Keamanan</span>
          <h1>Registrasi &amp; Check-in Otomatis</h1>
          <p>Pencatatan data kunjungan, foto tamu, tanda tangan digital, dan departemen tujuan yang tersusun rapi.</p>
        </div>
      </article>
      <article class="auth-slide auth-slide-3">
        <div class="auth-slide-content">
          <span class="auth-eyebrow"><i></i>Laporan &amp; Monitoring</span>
          <h1>Data Kunjungan Siap Cetak</h1>
          <p>Rekap tamu harian hingga bulanan tersimpan otomatis dan siap diunduh untuk kebutuhan laporan.</p>
        </div>
      </article>
    </div>

    <div class="auth-slider-controls">
      <div class="auth-dots" id="authDots">
        <button class="auth-dot active" type="button" data-slide="0" aria-label="Slide 1"></button>
        <button class="auth-dot" type="button" data-slide="1" aria-label="Slide 2"></button>
        <button class="auth-dot" type="button" data-slide="2" aria-label="Slide 3"></button>
      </div>
      <div class="auth-arrows">
        <button class="auth-arrow" id="authPrev" type="button" aria-label="Slide sebelumnya">‹</button>
        <button class="auth-arrow" id="authNext" type="button" aria-label="Slide berikutnya">›</button>
      </div>
    </div>
    <div class="auth-progress"><span id="authProgress" class="run"></span></div>
  </section>

  <section class="auth-panel">
    <div class="lock-card">
      <div class="auth-mobile-brand">
        <span class="auth-brand-logo">C</span>
        <div>
          <strong>CATAMU</strong>
          <span>Buku Tamu Digital</span>
        </div>
      </div>

      <div class="lock-logo">C</div>
      <h2 id="lockOfficeName">{{ $tenant ? $officeName : 'Masuk ke CATAMU' }}</h2>
      <p id="lockMessage">{{ $tenant ? 'Sesi aplikasi dikunci. Pilih akun untuk masuk kembali.' : 'Pilih metode masuk untuk mengelola kunjungan kantor Anda' }}</p>

      <input type="hidden" id="unlockMode" value="{{ $mode }}" />
      <div class="lock-mode-tabs" role="tablist" aria-label="Masuk sebagai">
        <button class="lock-mode-tab @if ($mode === 'owner') active @endif" type="button" role="tab" data-mode="owner" aria-selected="{{ $mode === 'owner' ? 'true' : 'false' }}">Owner / Admin</button>
        <button class="lock-mode-tab @if ($mode === 'team') active @endif" type="button" role="tab" data-mode="team" aria-selected="{{ $mode === 'team' ? 'true' : 'false' }}">Anggota Tim</button>
        @if ($showSuperAdminTempLogin)
        <button class="lock-mode-tab @if ($mode === 'superadmin') active @endif" type="button" role="tab" data-mode="superadmin" aria-selected="{{ $mode === 'superadmin' ? 'true' : 'false' }}">Super Admin (sementara)</button>
        @endif
      </div>

      <div class="lock-login-grid">
        <div id="ownerUnlockFields" @if ($mode !== 'owner') hidden @endif>
          @if ($ownerHasPin)
          <form id="pinForm" method="POST" action="{{ route('login.pin') }}">
            @csrf
            <label for="unlockPin">PIN Owner</label><input class="field" id="unlockPin" name="pin" type="password" inputmode="numeric" maxlength="6" placeholder="••••" autocomplete="off" />
          </form>
          @endif
        </div>
        <div id="teamUnlockFields" @if ($mode !== 'team') hidden @endif>
          <form id="teamLoginForm" method="POST" action="{{ route('login.team') }}">
            @csrf
            <div style="display:grid;gap:10px">
              <div>
                <label for="teamLoginCredential">Email / Nomor HP</label>
                <div class="field-icon-wrap">
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                  <input class="field" id="teamLoginCredential" name="credential" value="{{ old('credential') }}" autocomplete="username" placeholder="nama@kantor.id / 08xxxxxxxxxx" />
                </div>
              </div>
              <div>
                <label for="teamLoginPassword">Password</label>
                <div class="field-icon-wrap">
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                  <input class="field" id="teamLoginPassword" name="password" type="password" autocomplete="current-password" placeholder="Password anggota" />
                </div>
              </div>
            </div>
          </form>
        </div>
        @if ($showSuperAdminTempLogin)
        <div id="superadminUnlockFields" @if ($mode !== 'superadmin') hidden @endif>
          <form id="superadminLoginForm" method="POST" action="{{ route('login.superadmin.temp') }}">
            @csrf
            <div style="display:grid;gap:10px">
              <div>
                <label for="superadminLoginEmail">Email</label>
                <div class="field-icon-wrap">
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                  <input class="field" id="superadminLoginEmail" name="email" type="email" value="{{ old('mode') === 'superadmin' ? old('email') : '' }}" autocomplete="username" placeholder="nama@gmail.com" />
                </div>
              </div>
              <div>
                <label for="superadminLoginPassword">Password</label>
                <div class="field-icon-wrap">
                  <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                  <input class="field" id="superadminLoginPassword" name="password" type="password" autocomplete="current-password" placeholder="Password super admin" />
                </div>
              </div>
            </div>
          </form>
        </div>
        @endif
      </div>

      <button class="btn btn-primary" id="unlockBtn" type="button">Masuk</button>
      <a class="btn lock-google-btn" id="googleLoginBtn" href="{{ route('google.redirect') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M23.5 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.45a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.57-5.17 3.57-8.81Z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.92l-3.88-3c-1.07.72-2.45 1.15-4.06 1.15-3.13 0-5.78-2.11-6.72-4.95H1.27v3.1A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.28 14.28a7.2 7.2 0 0 1 0-4.56v-3.1H1.27a12 12 0 0 0 0 10.76l4.01-3.1Z"/><path fill="#EA4335" d="M12 4.77c1.76 0 3.34.61 4.59 1.8l3.44-3.44A11.5 11.5 0 0 0 12 0 12 12 0 0 0 1.27 6.62l4.01 3.1C6.22 6.88 8.87 4.77 12 4.77Z"/></svg>
        <span>Masuk dengan Google</span>
      </a>

      <p class="lock-login-note"><strong id="unlockHelpLabel">Informasi Owner:</strong> <span id="unlockHelp"></span></p>

      @if ($tenant)
      <form method="POST" action="{{ route('login.switch') }}" class="lock-switch-form">
        @csrf
        <button type="submit" class="lock-switch-btn">Gunakan akun kantor lain</button>
      </form>
      @else
      <p class="lock-switch-form"><a class="lock-switch-btn" href="{{ route('landing') }}">← Kembali ke beranda CATAMU</a></p>
      @endif

      <p class="lock-foot">© {{ date('Y') }} CATAMU • Buku Tamu Digital &amp; Manajemen Kunjungan</p>
    </div>
  </section>
</div>

<div class="toast" id="toast"></div>

<script>
(() => {
  const $ = id => document.getElementById(id);
  const hasPin = @json($ownerHasPin);
  const locked = @json((bool) $tenant);

  function toast(message){
    $('toast').textContent = message;
    $('toast').classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => $('toast').classList.remove('show'), 3200);
  }

  function renderMode(){
    const mode = $('unlockMode').value;
    $('ownerUnlockFields').hidden = mode !== 'owner';
    $('teamUnlockFields').hidden = mode !== 'team';
    if($('superadminUnlockFields')) $('superadminUnlockFields').hidden = mode !== 'superadmin';
    const ownerViaGoogle = mode === 'owner' && !hasPin;
    $('unlockBtn').hidden = ownerViaGoogle;
    $('googleLoginBtn').hidden = mode !== 'owner';
    $('googleLoginBtn').classList.toggle('btn-primary', ownerViaGoogle);
    if(mode === 'owner'){
      $('unlockHelpLabel').textContent = 'Informasi Owner:';
      $('unlockHelp').textContent = hasPin
        ? 'Masukkan PIN Owner 4–6 digit, atau masuk ulang dengan akun Google Owner.'
        : (locked ? 'Owner belum memiliki PIN. Masuk kembali dengan akun Google Owner.' : 'Gunakan akun Google terdaftar untuk mengelola kantor, tim, dan langganan CATAMU.');
      if(hasPin) setTimeout(() => $('unlockPin')?.focus(), 50);
    }else if(mode === 'superadmin'){
      $('unlockHelpLabel').textContent = 'Sementara:';
      $('unlockHelp').textContent = 'Jalur sementara sebelum login Google diaktifkan. Akan otomatis hilang begitu Google dikonfigurasi.';
      setTimeout(() => $('superadminLoginEmail')?.focus(), 50);
    }else{
      $('unlockHelpLabel').textContent = 'Informasi Anggota Tim:';
      $('unlockHelp').textContent = 'Masuk menggunakan email atau nomor HP anggota aktif dan password yang dibuat Owner pada Kelola Tim.';
    }
  }

  function submit(){
    const mode = $('unlockMode').value;
    if(mode === 'team'){
      if(!$('teamLoginCredential').value.trim() || !$('teamLoginPassword').value){ toast('Isi email/nomor HP dan password anggota.'); return; }
      $('teamLoginForm').submit();
    }else if(mode === 'superadmin'){
      if(!$('superadminLoginEmail').value.trim() || !$('superadminLoginPassword').value){ toast('Isi email dan password super admin.'); return; }
      $('superadminLoginForm').submit();
    }else if(hasPin){
      if(!$('unlockPin').value.trim()){ toast('Masukkan PIN Owner.'); $('unlockPin').focus(); return; }
      $('pinForm').submit();
    }
  }

  document.querySelectorAll('.lock-mode-tab').forEach(tab => tab.addEventListener('click', () => {
    document.querySelectorAll('.lock-mode-tab').forEach(t => {
      const on = t === tab;
      t.classList.toggle('active', on);
      t.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    $('unlockMode').value = tab.dataset.mode;
    $('unlockMode').dispatchEvent(new Event('change'));
  }));

  $('unlockMode').addEventListener('change', renderMode);
  $('unlockBtn').addEventListener('click', submit);
  ['unlockPin', 'teamLoginCredential', 'teamLoginPassword', 'superadminLoginEmail', 'superadminLoginPassword'].forEach(id => $(id)?.addEventListener('keydown', e => {
    if(e.key === 'Enter'){ e.preventDefault(); submit(); }
  }));

  renderMode();
  @if (session('error'))
  toast(@json(session('error')));
  @elseif ($errors->any())
  toast(@json($errors->first()));
  @endif
})();

(() => {
  const slides = Array.from(document.querySelectorAll('.auth-slide'));
  if(!slides.length) return;

  const dots = Array.from(document.querySelectorAll('.auth-dot'));
  const prevBtn = document.getElementById('authPrev');
  const nextBtn = document.getElementById('authNext');
  const progress = document.getElementById('authProgress');
  const visual = document.querySelector('.auth-visual');

  let current = 0;
  let timer;

  function restartProgress(){
    if(!progress) return;
    progress.classList.remove('run');
    void progress.offsetWidth;
    progress.classList.add('run');
  }

  function show(index){
    current = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => slide.classList.toggle('active', i === current));
    dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
    restartProgress();
  }

  function start(){
    clearInterval(timer);
    timer = setInterval(() => show(current + 1), 5000);
    restartProgress();
  }

  dots.forEach(dot => dot.addEventListener('click', () => { show(Number(dot.dataset.slide)); start(); }));
  prevBtn?.addEventListener('click', () => { show(current - 1); start(); });
  nextBtn?.addEventListener('click', () => { show(current + 1); start(); });
  visual?.addEventListener('mouseenter', () => { clearInterval(timer); progress?.classList.remove('run'); });
  visual?.addEventListener('mouseleave', start);
  document.addEventListener('visibilitychange', () => { document.hidden ? clearInterval(timer) : start(); });

  start();
})();
</script>
@endsection
