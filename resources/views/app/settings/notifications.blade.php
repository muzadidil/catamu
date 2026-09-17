<div id="settingsNotificationsView" class="settings-subview">
  <div class="settings-subview-head"><button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><div><p>Atur notifikasi kunjungan di aplikasi dan perangkat.</p></div></div>
  <form id="notificationSettingsForm">
    <div class="settings-card">
      <div class="settings-card-head"><div><h3>Notifikasi Aplikasi</h3><p>Pilih kejadian yang akan masuk ke Pusat Notifikasi.</p></div></div>
      <div class="settings-card-body">
        <div class="switch-row"><div><strong>Pusat Notifikasi</strong><small>Simpan dan tampilkan riwayat notifikasi di aplikasi.</small></div><label class="toggle"><input type="checkbox" id="notificationInApp" checked><span></span></label></div>
        <div class="switch-row"><div><strong>Tamu Baru / Check-in</strong><small>Beri notifikasi saat registrasi tamu baru berhasil.</small></div><label class="toggle"><input type="checkbox" id="notificationCheckIn" checked><span></span></label></div>
        <div class="switch-row"><div><strong>Check-out Tamu</strong><small>Beri notifikasi saat kunjungan selesai.</small></div><label class="toggle"><input type="checkbox" id="notificationCheckOut" checked><span></span></label></div>
        <div class="switch-row"><div><strong>Perubahan Data Tamu</strong><small>Beri notifikasi saat data kunjungan diperbarui.</small></div><label class="toggle"><input type="checkbox" id="notificationUpdates" checked><span></span></label></div>
      </div>
    </div>
    <div class="settings-card">
      <div class="settings-card-head"><div><h3>Notifikasi Browser / HP</h3><p>Munculkan pemberitahuan sistem jika browser mendukung dan izin diberikan.</p></div></div>
      <div class="settings-card-body">
        <div class="switch-row"><div><strong>Aktifkan Notifikasi Perangkat</strong><small>Notifikasi sistem tetap mengikuti pilihan event di atas.</small></div><label class="toggle"><input type="checkbox" id="notificationBrowser"><span></span></label></div>
        <div style="height:12px"></div>
        <div class="notification-permission"><div><strong>Status Izin Browser</strong><small id="notificationPermissionStatus">Memeriksa dukungan browser...</small></div><button class="btn" id="requestNotificationPermissionBtn" type="button">Minta Izin</button></div>
      </div>
      <div class="settings-card-foot"><button class="btn btn-primary" type="submit">Simpan Notifikasi</button></div>
    </div>
  </form>
</div>
