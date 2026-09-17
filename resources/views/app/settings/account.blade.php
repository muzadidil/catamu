<div id="settingsAccountView" class="settings-subview">
  <div class="settings-subview-head"><button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><div><p>Profil pengguna dan penguncian aplikasi.</p></div></div>
  <div class="settings-card">
    <form id="accountForm">
      <div class="settings-card-body">
        <div class="profile-panel">
          <div class="profile-avatar" id="profileAvatar">AD</div>
          <div><h3 id="profileDisplayName">Administrator</h3><p id="profileDisplayRole">Admin Aplikasi</p></div>
        </div>
        <div class="settings-grid">
          <div class="form-group"><label>Nama Pengguna</label><input class="field" id="profileName" required /></div>
          <div class="form-group"><label>Email</label><input class="field" id="profileEmail" type="email" placeholder="admin@kantor.id" /></div>
          <div class="form-group"><label>Nomor HP</label><input class="field" id="profilePhone" inputmode="tel" /></div>
          <div class="form-group"><label>Foto Profil</label><input class="field" id="profilePhoto" type="file" accept="image/*" /></div>
          <div class="form-group"><label>PIN Aplikasi (4–6 digit)</label><input class="field" id="profilePin" type="password" inputmode="numeric" maxlength="6" placeholder="Kosongkan jika tidak diubah" /><div class="hint">PIN dipakai untuk membuka layar kunci. Login utama Owner tetap melalui akun Google.</div></div>
          <div class="form-group"><label>Konfirmasi PIN</label><input class="field" id="profilePinConfirm" type="password" inputmode="numeric" maxlength="6" /></div>
        </div>
      </div>
      <div class="settings-card-foot"><button class="btn" type="button" id="removeProfilePhoto">Hapus Foto</button><button class="btn" type="button" id="removePinBtn">Hapus PIN</button><button class="btn btn-primary" type="submit">Simpan Akun</button></div>
    </form>
  </div>
  <div class="settings-card account-danger-zone">
    <div class="settings-card-head">
      <div><h3 class="danger-title"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5M12 18h.01"/></svg>Zona Berbahaya</h3><p>Tindakan permanen untuk akun kantor dan seluruh datanya di server.</p></div>
    </div>
    <div class="settings-card-body">
      <div class="danger-copy">
        <strong>Hapus akun dan seluruh data terkait</strong>
        <span>Fitur ini menghapus akun kantor beserta seluruh datanya secara permanen dari server.</span>
        <ul class="danger-list">
          <li>Profil, foto profil, PIN, dan sesi aplikasi</li>
          <li>Data tamu, tim, departemen, notifikasi, masukan, dan rating</li>
          <li>Pengaturan aplikasi, status langganan, dan riwayat pembayaran</li>
        </ul>
      </div>
    </div>
    <div class="settings-card-foot"><span class="account-danger-note">Seluruh anggota tim juga tidak dapat masuk lagi setelah akun dihapus.</span><button class="btn btn-danger" type="button" id="deleteAccountBtn">Hapus Akun</button></div>
  </div>
</div>
