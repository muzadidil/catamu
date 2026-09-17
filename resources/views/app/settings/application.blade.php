<div id="settingsApplicationView" class="settings-subview">
  <div class="settings-subview-head"><button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><div><p>Identitas kantor dan preferensi tampilan.</p></div></div>
  <div class="settings-card">
    <form id="settingsForm">
      <div class="settings-card-body">
        <div class="settings-grid">
          <div class="form-group"><label>Nama Kantor</label><input class="field" id="settingOffice" /></div>
          <div class="form-group"><label>Jam Operasional</label><input class="field" id="settingHours" /></div>
          <div class="form-group full"><label>Alamat Kantor</label><textarea class="field" id="settingAddress"></textarea></div>
          <div class="form-group"><label>Nama Petugas Resepsionis</label><input class="field" id="settingOfficer" /></div>
          <div class="form-group"><label>Nomor Telepon / WhatsApp Kantor</label><input class="field" id="settingPhone" inputmode="tel" /></div>
          <div class="form-group"><label>Email Kantor</label><input class="field" id="settingEmail" type="email" /></div>
          <div class="form-group"><label>Tema</label><select class="field" id="settingTheme"><option value="system">Ikuti Perangkat</option><option value="light">Light</option><option value="dark">Dark</option></select></div>
          <div class="form-group"><label>Format Tanggal</label><select class="field" id="settingDateFormat"><option value="long">16 September 2026</option><option value="short">16/09/2026</option></select></div>
          <div class="form-group"><label>Format Jam</label><select class="field" id="settingTimeFormat"><option value="24">24 Jam</option><option value="12">12 Jam (AM/PM)</option></select></div>
          <div class="form-group"><label>Default Jumlah Pengunjung</label><input class="field" id="settingDefaultPeople" type="number" min="1" max="100" /></div>
          <div class="form-group full"><div class="switch-row"><div><strong>Panduan Suara Tambah Tamu</strong><small>Aktifkan instruksi suara saat mengisi Registrasi Tamu baru.</small></div><label class="toggle"><input type="checkbox" id="settingGuestVoiceEnabled" checked><span></span></label></div></div>
        </div>
      </div>
      <div class="settings-card-foot"><button type="button" class="btn btn-danger" id="clearDataBtn">Hapus Semua Data Tamu</button><button type="submit" class="btn btn-primary">Simpan Pengaturan</button></div>
    </form>
  </div>
</div>
