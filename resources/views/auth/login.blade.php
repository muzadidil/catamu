@extends('layouts.catamu')

@section('title', 'Masuk • CATAMU')

@section('body')
@php
  $mode = old('mode', 'owner');
@endphp
<div class="app-lock show" id="appLock" aria-hidden="false">
  <div class="lock-card">
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
          <div style="display:grid;gap:10px"><div><label for="teamLoginCredential">Email / Nomor HP</label><input class="field" id="teamLoginCredential" name="credential" value="{{ old('credential') }}" autocomplete="username" placeholder="nama@kantor.id / 08xxxxxxxxxx" /></div><div><label for="teamLoginPassword">Password</label><input class="field" id="teamLoginPassword" name="password" type="password" autocomplete="current-password" placeholder="Password anggota" /></div></div>
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
  if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) document.body.classList.add('dark');

  renderMode();
  @if (session('error'))
  toast(@json(session('error')));
  @elseif ($errors->any())
  toast(@json($errors->first()));
  @endif
})();
</script>
@endsection
