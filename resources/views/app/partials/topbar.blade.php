<header class="topbar">
  <div class="top-left">
    <div class="page-title">
      <h2 id="headerTitle">Dashboard</h2>
      <p id="headerSubtitle">Ringkasan kunjungan kantor hari ini</p>
    </div>
  </div>
  <div class="top-actions">
    <div class="clock"><strong id="clock">00:00:00</strong><small id="dateText">-</small></div>
    <div class="office-info-wrap">
      <button class="icon-btn office-info-btn" id="officeInfoBtn" type="button" title="Informasi Kantor" aria-label="Buka informasi kantor" aria-expanded="false">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/></svg>
      </button>
      <div class="office-info-panel" id="officeInfoPanel" role="dialog" aria-label="Informasi Kantor">
        <div class="office-info-head">
          <span class="office-info-head-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5l8-3 8 3v16"/><path d="M9 8h1m4 0h1M9 12h1m4 0h1M9 16h1m4 0h1M2 21h20"/></svg></span>
          <div><h3>Informasi Kantor</h3><p>Informasi penerimaan dan kontak kantor</p></div>
        </div>
        <div class="office-info-list">
          <div class="office-info-row"><span>Nama Kantor</span><strong id="officeInfoOffice">Kantor Utama</strong></div>
          <div class="office-info-row"><span>Alamat</span><strong id="officeInfoAddress">Alamat kantor belum diatur</strong></div>
          <div class="office-info-row"><span>Jam Operasional</span><strong id="officeInfoHours">08.00–17.00 WIB</strong></div>
          <div class="office-info-row"><span>Petugas</span><strong id="officeInfoOfficer">Resepsionis</strong></div>
          <div class="office-info-row"><span>Telepon / WA</span><strong id="officeInfoPhone">Belum diatur</strong></div>
          <div class="office-info-row"><span>Email</span><strong id="officeInfoEmail">Belum diatur</strong></div>
          <div class="office-info-row"><span>Link Cek-in Tamu</span><strong><a class="office-info-link" id="officeInfoCheckinLink" href="{{ $state['links']['checkinUrl'] }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', $state['links']['checkinUrl']) }}</a></strong></div>
        </div>
        <div class="office-info-foot">Data mengikuti Pengaturan Aplikasi. • <a class="office-info-link" id="officeInfoQrLink" href="{{ $state['links']['qrPosterUrl'] }}" target="_blank" rel="noopener">Cetak QR Cek-in</a></div>
      </div>
    </div>
    <div class="notification-wrap">
      <button class="icon-btn notification-btn" id="notificationBtn" type="button" title="Notifikasi" aria-label="Buka notifikasi" aria-expanded="false">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
        <span class="notification-badge" id="notificationBadge">0</span>
      </button>
      <div class="notification-panel" id="notificationPanel" role="dialog" aria-label="Pusat Notifikasi">
        <div class="notification-panel-head">
          <div><h3>Pusat Notifikasi</h3><p id="notificationPanelSummary">Belum ada notifikasi baru</p></div>
          <div class="notification-panel-actions"><button class="btn" id="markAllNotificationsRead" type="button">Baca semua</button><button class="btn" id="clearNotifications" type="button">Hapus</button></div>
        </div>
        <div class="notification-list" id="notificationList"></div>
        <div class="notification-panel-foot">Notifikasi dibagikan ke seluruh tim kantor.</div>
      </div>
    </div>
    <button class="icon-btn" id="themeBtn" title="Ubah tema" aria-label="Ubah tema"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 14.3A8.5 8.5 0 0 1 9.7 3.5 8.5 8.5 0 1 0 20.5 14.3Z"/></svg></button>
  </div>
</header>
