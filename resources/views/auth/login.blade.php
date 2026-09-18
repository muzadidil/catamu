@extends('layouts.catamu')

@section('title', 'Masuk • CATAMU')

@section('body')
@php
  $mode = old('mode', 'owner');
@endphp
<div class="app-lock show" id="appLock" aria-hidden="false">
  <section class="auth-visual" aria-hidden="true">
    <div class="auth-brand-top">
      <span class="auth-brand-logo">CA</span>
      <div class="auth-brand-copy">
        <strong>CATAMU</strong>
        <span>Buku Tamu Digital untuk Kantor</span>
      </div>
    </div>

    <div class="auth-slides" id="authSlides">
      <article class="auth-slide active">
        <div class="auth-slide-content">
          <span class="auth-eyebrow">Buku Tamu Digital</span>
          <h1>Selamat datang di CATAMU</h1>
          <p>Masuk untuk mencatat tamu, memantau kunjungan, dan mengelola tim kantor Anda secara real-time.</p>
        </div>
      </article>
      <article class="auth-slide">
        <div class="auth-slide-content">
          <span class="auth-eyebrow">Cek-in Mandiri</span>
          <h1>Tamu cek-in sendiri lewat QR</h1>
          <p>Tanpa antre dan tanpa buku kertas — tamu tinggal scan, isi data, tim langsung mendapat notifikasi.</p>
        </div>
      </article>
      <article class="auth-slide">
        <div class="auth-slide-content">
          <span class="auth-eyebrow">Laporan Siap Cetak</span>
          <h1>Setiap kunjungan tercatat rapi</h1>
          <p>Foto, tanda tangan, dan tujuan kunjungan tersimpan otomatis dan siap diunduh kapan saja.</p>
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
        <span class="auth-brand-logo">CA</span>
        <div>
          <strong>CATAMU</strong>
          <span>Buku Tamu Digital untuk Kantor</span>
        </div>
      </div>

      <div class="lock-logo">CA</div>
      <h2 id="lockOfficeName">{{ $officeName }}</h2>
      <p id="lockMessage">{{ $tenant ? 'Sesi aplikasi dikunci. Pilih akun untuk masuk kembali.' : 'Pilih akun untuk masuk ke CATAMU.' }}</p>
      <div class="lock-login-grid">
        <div><label for="unlockMode">Masuk sebagai</label><select class="field" id="unlockMode"><option value="owner" @selected($mode === 'owner')>Owner / Administrator</option><option value="team" @selected($mode === 'team')>Anggota Tim</option></select></div>
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
      </div>
      <p class="lock-login-note" id="unlockHelp"></p>
      <div style="height:10px"></div>
      <button class="btn btn-primary" id="unlockBtn" type="button" style="width:100%">Masuk</button>
      <a class="btn lock-google-btn" id="googleLoginBtn" href="{{ route('google.redirect') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M23.5 12.27c0-.85-.08-1.67-.22-2.45H12v4.64h6.45a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.57-5.17 3.57-8.81Z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.94-2.92l-3.88-3c-1.07.72-2.45 1.15-4.06 1.15-3.13 0-5.78-2.11-6.72-4.95H1.27v3.1A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.28 14.28a7.2 7.2 0 0 1 0-4.56v-3.1H1.27a12 12 0 0 0 0 10.76l4.01-3.1Z"/><path fill="#EA4335" d="M12 4.77c1.76 0 3.34.61 4.59 1.8l3.44-3.44A11.5 11.5 0 0 0 12 0 12 12 0 0 0 1.27 6.62l4.01 3.1C6.22 6.88 8.87 4.77 12 4.77Z"/></svg>
        <span>Masuk dengan Google</span>
      </a>
      @if ($tenant)
      <form method="POST" action="{{ route('login.switch') }}" class="lock-switch-form">
        @csrf
        <button type="submit" class="lock-switch-btn">Gunakan akun kantor lain</button>
      </form>
      @else
      <p class="lock-switch-form"><a class="lock-switch-btn" href="{{ route('landing') }}">← Kembali ke beranda CATAMU</a></p>
      @endif
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
    const ownerViaGoogle = mode === 'owner' && !hasPin;
    $('unlockBtn').hidden = ownerViaGoogle;
    $('googleLoginBtn').hidden = mode !== 'owner';
    $('googleLoginBtn').classList.toggle('btn-primary', ownerViaGoogle);
    if(mode === 'owner'){
      $('unlockHelp').textContent = hasPin
        ? 'Masukkan PIN Owner 4–6 digit, atau masuk ulang dengan akun Google Owner.'
        : (locked ? 'Owner belum memiliki PIN. Masuk kembali dengan akun Google Owner.' : 'Owner masuk dengan akun Google. Kantor baru otomatis dibuat saat pertama kali masuk.');
      if(hasPin) setTimeout(() => $('unlockPin')?.focus(), 50);
    }else{
      $('unlockHelp').textContent = 'Masuk menggunakan email atau nomor HP anggota aktif dan password yang dibuat Owner pada Kelola Tim.';
    }
  }

  function submit(){
    if($('unlockMode').value === 'team'){
      if(!$('teamLoginCredential').value.trim() || !$('teamLoginPassword').value){ toast('Isi email/nomor HP dan password anggota.'); return; }
      $('teamLoginForm').submit();
    }else if(hasPin){
      if(!$('unlockPin').value.trim()){ toast('Masukkan PIN Owner.'); $('unlockPin').focus(); return; }
      $('pinForm').submit();
    }
  }

  $('unlockMode').addEventListener('change', renderMode);
  $('unlockBtn').addEventListener('click', submit);
  ['unlockPin', 'teamLoginCredential', 'teamLoginPassword'].forEach(id => $(id)?.addEventListener('keydown', e => {
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
