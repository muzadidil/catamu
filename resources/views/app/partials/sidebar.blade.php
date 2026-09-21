<aside class="sidebar" id="sidebar">
  <div class="brand">
    @php ($brandLogoUrl = \App\Support\TenantBranding::forView($tenant)['logoUrl'])
    @if ($brandLogoUrl)
    <div class="brand-logo brand-logo-img"><img src="{{ $brandLogoUrl }}" alt="Logo {{ $tenant->officeName() }}" /></div>
    @else
    <div class="brand-logo">AD</div>
    @endif
    <div>
      <h1>adatamu.id</h1>
      <small id="brandOffice">Kantor Utama</small>
    </div>
  </div>
  <nav class="nav">
    <button class="nav-btn active" data-page="dashboard"><span class="nav-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 10.5 12 3l8.5 7.5"/><path d="M5.5 9.5V21h13V9.5"/><path d="M9.5 21v-6h5v6"/></svg></span><span>Dashboard</span></button>
    <button class="nav-btn" data-page="guests"><span class="nav-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 20v-1.5A5.5 5.5 0 0 1 9 13h1"/><path d="M15.5 6a2.8 2.8 0 0 1 0 5.3"/><path d="M15 13.3a5 5 0 0 1 5.5 5V20"/></svg></span><span>Daftar Tamu</span></button>
    <button class="nav-btn" data-page="form"><span class="nav-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span><span>Registrasi Tamu</span></button>
    <button class="nav-btn" data-page="settings"><span class="nav-ico"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.07.07-2.78 2.78-.07-.07A1.8 1.8 0 0 0 15 19.4a1.8 1.8 0 0 0-1.1 1.65V21h-3.8v-.1A1.8 1.8 0 0 0 9 19.4a1.8 1.8 0 0 0-1.98.36l-.07.07-2.78-2.78.07-.07A1.8 1.8 0 0 0 4.6 15 1.8 1.8 0 0 0 3 13.9H3v-3.8h.1A1.8 1.8 0 0 0 4.6 9a1.8 1.8 0 0 0-.36-1.98l-.07-.07 2.78-2.78.07.07A1.8 1.8 0 0 0 9 4.6a1.8 1.8 0 0 0 1.1-1.5V3h3.8v.1A1.8 1.8 0 0 0 15 4.6a1.8 1.8 0 0 0 1.98-.36l.07-.07 2.78 2.78-.07.07A1.8 1.8 0 0 0 19.4 9a1.8 1.8 0 0 0 1.5 1.1h.1v3.8h-.1A1.8 1.8 0 0 0 19.4 15Z"/></svg></span><span>Pengaturan</span></button>
  </nav>
  <div class="sidebar-foot">
    <div class="sidebar-system"><span class="sidebar-system-dot"></span><strong>Sistem Online</strong></div>
    <span>Data tersimpan aman di server</span>
    <small>Versi {{ config('catamu.version') }}</small>
  </div>
</aside>
