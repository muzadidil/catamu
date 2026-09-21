{{-- Hanya dirender untuk role yang boleh mengubah pengaturan. --}}
@if (! empty($state['branding']))
<div id="settingsBrandingView" class="settings-subview">
  <div class="settings-subview-head">
    <button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button>
    <div><p>Atur logo dan gambar yang tampil di halaman login khusus kantor Anda.</p></div>
  </div>

  <div class="settings-card">
    <div class="settings-card-head"><div><h3>Logo Kantor</h3><p>Dipakai di halaman login kantor, sidebar aplikasi, favicon tab browser, dan ikon aplikasi saat dipasang di HP. Disarankan PNG latar transparan dan bentuk persegi, sisi terpanjang minimal 512 piksel.</p></div></div>
    <div class="settings-card-body">
      <div class="branding-logo-row">
        <div class="branding-logo-preview" id="brandingLogoPreview"><span id="brandingLogoInitial">K</span></div>
        <div class="branding-logo-actions">
          <input type="file" id="brandingLogoInput" accept="image/png,image/jpeg,image/webp" hidden />
          <button class="btn btn-primary" type="button" id="brandingLogoPickBtn">Pilih Logo</button>
          <button class="btn" type="button" id="brandingLogoRemoveBtn">Hapus Logo</button>
          <p class="hint">Format PNG, JPG, atau WEBP. Maksimal 2 MB.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="settings-card">
    <div class="settings-card-head"><div><h3>Link Halaman Login Kantor</h3><p>Bagikan link ini ke tim Anda agar mereka masuk lewat halaman berlogo kantor.</p></div></div>
    <div class="settings-card-body">
      <div class="aff-link-box">
        <span class="aff-link-value" id="brandingLoginUrl">-</span>
        <div class="aff-link-actions">
          <button class="btn btn-compact" type="button" id="brandingCopyBtn">Salin</button>
          <a class="btn btn-compact" id="brandingOpenBtn" target="_blank" rel="noopener">Buka</a>
        </div>
      </div>
    </div>
  </div>

  <div class="settings-card">
    <div class="settings-card-head">
      <div><h3>Gambar &amp; Teks Halaman Login</h3><p id="brandingSlideCountText">Belum ada slide. Halaman login memakai tampilan bawaan adatamu.id.</p></div>
      <button class="btn btn-primary" type="button" id="brandingSlideAddBtn">Tambah Slide</button>
    </div>
    <div class="settings-card-body">
      <div class="branding-slides" id="brandingSlideList"></div>
      <p class="hint" id="brandingSlideHint">Slide ditampilkan bergantian di panel kiri halaman login. Kosongkan semua slide untuk kembali ke tampilan bawaan.</p>
    </div>
  </div>
</div>
@endif
