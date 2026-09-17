<div class="modal" id="teamModal" aria-labelledby="teamModalTitle" role="dialog" aria-modal="true">
  <div class="modal-panel team-modal-panel">
    <div class="modal-head">
      <div><h3 id="teamModalTitle">Tambah Anggota</h3><p id="teamModalSubtitle">Lengkapi data dan hak akses anggota tim.</p></div>
      <button class="icon-btn" type="button" data-close="teamModal" aria-label="Tutup">×</button>
    </div>
    <form id="teamForm">
      <div class="modal-body">
        <input type="hidden" id="teamEditId" />
        <div class="settings-grid team-modal-grid">
          <div class="form-group"><label for="teamName">Nama</label><input class="field" id="teamName" required placeholder="Nama anggota" /></div>
          <div class="form-group"><label for="teamRole">Role</label><select class="field" id="teamRole"><option>Admin</option><option>Resepsionis</option><option>Viewer</option></select></div>
          <div class="form-group"><label for="teamEmail">Email</label><input class="field" id="teamEmail" type="email" placeholder="nama@kantor.id" /></div>
          <div class="form-group"><label for="teamPhone">Nomor HP</label><input class="field" id="teamPhone" inputmode="tel" placeholder="08xxxxxxxxxx" /></div>
          <div class="form-group"><label for="teamPassword">Password</label><div class="team-password-wrap"><input class="field" id="teamPassword" type="password" minlength="6" autocomplete="new-password" placeholder="Minimal 6 karakter" /><button class="team-password-toggle" type="button" data-password-toggle="teamPassword">Tampilkan</button></div><small class="team-password-help" id="teamPasswordHelp">Minimal 6 karakter.</small></div>
          <div class="form-group"><label for="teamPasswordConfirm">Konfirmasi Password</label><div class="team-password-wrap"><input class="field" id="teamPasswordConfirm" type="password" minlength="6" autocomplete="new-password" placeholder="Ulangi password" /><button class="team-password-toggle" type="button" data-password-toggle="teamPasswordConfirm">Tampilkan</button></div><small class="team-password-help">Harus sama dengan password.</small></div>
          <div class="form-group full">
            <label>Hak Akses</label>
            <div class="permission-grid">
              <label class="check-chip"><input type="checkbox" id="permGuests" checked /><span class="permission-copy"><span class="permission-title">Data Tamu</span><small class="permission-note">Kelola data kunjungan tamu.</small></span></label>
              <label class="check-chip"><input type="checkbox" id="permReports" /><span class="permission-copy"><span class="permission-title">Laporan</span><small class="permission-note">Akses ringkasan dan laporan.</small></span></label>
              <label class="check-chip"><input type="checkbox" id="permSettings" /><span class="permission-copy"><span class="permission-title">Pengaturan</span><small class="permission-note">Kelola konfigurasi aplikasi.</small></span></label>
            </div>
          </div>
          <div class="form-group full"><div class="switch-row"><div><strong>Status Anggota</strong><small>Anggota aktif dapat masuk ke aplikasi dengan email/nomor HP dan password.</small></div><label class="toggle"><input type="checkbox" id="teamActive" checked /><span></span></label></div></div>
        </div>
      </div>
      <div class="modal-foot"><button class="btn" type="button" data-close="teamModal">Batal</button><button class="btn btn-primary" type="submit" id="teamSaveBtn">Simpan Anggota</button></div>
    </form>
  </div>
</div>
