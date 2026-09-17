<div id="settingsMenuView" class="settings-menu">
  <div class="settings-group">
    <h3 class="settings-group-title">Langganan</h3>
    <button class="settings-item" type="button" data-setting-action="subscription">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="M3 7l4 3 5-6 5 6 4-3-2 10H5L3 7Z"/><path d="M6 20h12"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Berlangganan</span><span class="settings-item-note" id="subscriptionMenuNote">{{ $state['subscription']['plan'] }}</span></span><span class="settings-item-arrow">›</span>
    </button>
  </div>

  <div class="settings-group">
    <h3 class="settings-group-title">Akun &amp; Organisasi</h3>
    <button class="settings-item" type="button" data-setting-action="account">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="3.5"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Akun</span><span class="settings-item-note" id="accountMenuNote">Profil pengguna &amp; PIN</span></span><span class="settings-item-arrow">›</span>
    </button>
    <button class="settings-item" type="button" data-setting-action="team">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3.5 20v-1.5A5.5 5.5 0 0 1 9 13h1"/><path d="M15 5.5a3 3 0 0 1 0 5.5"/><path d="M15 13a5.5 5.5 0 0 1 5.5 5.5V20"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Kelola Tim</span><span class="settings-item-note" id="teamMenuNote">0 anggota</span></span><span class="settings-item-arrow">›</span>
    </button>
    <button class="settings-item" type="button" data-setting-action="departments">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="M4 21V5l8-3 8 3v16"/><path d="M9 8h1m4 0h1M9 12h1m4 0h1M9 16h1m4 0h1M2 21h20"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Departemen</span><span class="settings-item-note" id="departmentMenuNote">0 departemen</span></span><span class="settings-item-arrow">›</span>
    </button>
  </div>

  <div class="settings-group">
    <h3 class="settings-group-title">Aplikasi</h3>
    <button class="settings-item" type="button" data-setting-action="application">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="M4 7h10"/><circle cx="17" cy="7" r="2"/><path d="M20 17H10"/><circle cx="7" cy="17" r="2"/><path d="M4 12h5"/><circle cx="12" cy="12" r="2"/><path d="M15 12h5"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Aplikasi</span><span class="settings-item-note">Kantor, tampilan, tanggal &amp; jam</span></span><span class="settings-item-arrow">›</span>
    </button>
    <button class="settings-item" type="button" data-setting-action="guest-fields">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5"/><path d="m15 16 2 2 3-4"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Atur Data Tamu</span><span class="settings-item-note">Atur field Registrasi Tamu</span></span><span class="settings-item-arrow">›</span>
    </button>
    <button class="settings-item" type="button" data-setting-action="notifications">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Notifikasi</span><span class="settings-item-note" id="notificationMenuNote">Notifikasi aplikasi aktif</span></span><span class="settings-item-arrow">›</span>
    </button>
    <button class="settings-item" type="button" data-setting-action="download-desktop">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><rect x="6" y="2.5" width="12" height="19" rx="2"/><path d="M10 5h4M11 18.5h2"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Instal di HP</span><span class="settings-item-note">Pasang CATAMU di perangkat</span></span><span class="settings-item-arrow">›</span>
    </button>
  </div>

  <div class="settings-group">
    <h3 class="settings-group-title">Bantuan</h3>
    <button class="settings-item" type="button" data-setting-action="contact">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="M4 14v-4a8 8 0 0 1 16 0v4"/><path d="M4 14h3v5H5a1 1 0 0 1-1-1v-4Zm16 0h-3v5h2a1 1 0 0 0 1-1v-4Z"/><path d="M17 19c0 1.1-.9 2-2 2h-3"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Kontak Kami</span><span class="settings-item-note">WhatsApp, telepon, dan email</span></span><span class="settings-item-arrow">›</span>
    </button>
    <button class="settings-item" type="button" data-setting-action="feedback">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="M4 5h16v11H9l-5 4V5Z"/><path d="m15 3 2 2 4-4"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Masukan</span><span class="settings-item-note" id="feedbackMenuNote">Belum ada masukan</span></span><span class="settings-item-arrow">›</span>
    </button>
    <button class="settings-item" type="button" data-setting-action="rating">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Rating Aplikasi</span><span class="settings-item-note" id="ratingMenuNote">Belum memberi rating</span></span><span class="settings-item-arrow">›</span>
    </button>
  </div>

  <div class="settings-group">
    <h3 class="settings-group-title">Tentang</h3>
    <button class="settings-item" type="button" data-setting-action="license">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="M4 5.5A3.5 3.5 0 0 1 7.5 2H11v17H7.5A3.5 3.5 0 0 0 4 22V5.5Z"/><path d="M20 5.5A3.5 3.5 0 0 0 16.5 2H13v17h3.5A3.5 3.5 0 0 1 20 22V5.5Z"/><path d="M7 7h2m-2 4h2m6-4h2m-2 4h2"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">About</span><span class="settings-item-note">Kebijakan Privasi &amp; Syarat &amp; Ketentuan</span></span><span class="settings-item-arrow">›</span>
    </button>
  </div>

  <div class="settings-group">
    <h3 class="settings-group-title">Sesi</h3>
    <button class="settings-item danger" type="button" data-setting-action="logout">
      <span class="settings-item-icon"><svg viewBox="0 0 24 24"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/><path d="M14 8l4 4-4 4m4-4H8"/></svg></span>
      <span class="settings-item-main"><span class="settings-item-text">Keluar</span><span class="settings-item-note">Kunci sesi tanpa menghapus data</span></span><span class="settings-item-arrow">›</span>
    </button>
  </div>
</div>
