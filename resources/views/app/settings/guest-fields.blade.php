<div id="settingsGuestFieldsView" class="settings-subview">
  <div class="settings-subview-head"><button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><div><p>Atur field yang tampil dan wajib diisi pada Registrasi Tamu.</p></div></div>
  <form id="guestFieldSettingsForm">
    <div class="settings-card">
      <div class="settings-card-head"><div><h3>Field Registrasi Tamu</h3><p>Matikan Tampilkan untuk menyembunyikan field. Field yang disembunyikan otomatis tidak wajib.</p></div></div>
      <div class="settings-card-body">
        <div class="guest-field-settings-list">
        <div class="guest-field-setting-row is-locked" data-guest-field-setting="name">
          <div class="guest-field-setting-copy"><strong>Nama Lengkap</strong><small>Identitas utama tamu. Selalu tampil dan wajib.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked disabled><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required checked disabled><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="phone">
          <div class="guest-field-setting-copy"><strong>Nomor HP / WhatsApp</strong><small>Nomor kontak tamu.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required checked><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="company">
          <div class="guest-field-setting-copy"><strong>Instansi / Perusahaan</strong><small>Asal instansi atau perusahaan tamu.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="email">
          <div class="guest-field-setting-copy"><strong>Email</strong><small>Alamat email tamu.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="vehicle">
          <div class="guest-field-setting-copy"><strong>Plat Nomor</strong><small>Nomor polisi kendaraan tamu.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="meet">
          <div class="guest-field-setting-copy"><strong>Orang / Bagian yang Ditemui</strong><small>Tujuan orang atau departemen yang dikunjungi.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required checked><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="people">
          <div class="guest-field-setting-copy"><strong>Jumlah Pengunjung</strong><small>Jumlah orang dalam satu kunjungan.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="purpose">
          <div class="guest-field-setting-copy"><strong>Keperluan Kunjungan</strong><small>Tujuan atau kebutuhan kunjungan.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required checked><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="notes">
          <div class="guest-field-setting-copy"><strong>Catatan</strong><small>Catatan tambahan kunjungan.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="photo">
          <div class="guest-field-setting-copy"><strong>Foto Tamu</strong><small>Foto dokumentasi tamu.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required><span></span></span></label>
        </div>
        <div class="guest-field-setting-row" data-guest-field-setting="signature">
          <div class="guest-field-setting-copy"><strong>Tanda Tangan</strong><small>Tanda tangan digital tamu.</small></div>
          <label class="guest-field-setting-control"><span>Tampilkan</span><span class="toggle"><input type="checkbox" data-field-visible checked><span></span></span></label>
          <label class="guest-field-setting-control"><span>Wajib</span><span class="toggle"><input type="checkbox" data-field-required><span></span></span></label>
        </div>
        </div>
      </div>
      <div class="settings-card-foot"><button class="btn" type="button" id="resetGuestFieldSettingsBtn">Reset Pengaturan</button><button class="btn btn-primary" type="submit">Simpan</button></div>
    </div>
  </form>
</div>
