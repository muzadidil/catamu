<div id="settingsFeedbackView" class="settings-subview">
  <div class="settings-subview-head"><button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><div><p>Simpan saran, laporan masalah, atau ide pengembangan.</p></div></div>
  <div class="settings-card">
    <form id="feedbackForm">
      <div class="settings-card-body">
        <div class="settings-grid">
          <div class="form-group"><label>Kategori</label><select class="field" id="feedbackCategory"><option>Saran</option><option>Masalah</option><option>Fitur</option><option>Lainnya</option></select></div>
          <div class="form-group"><label>Judul</label><input class="field" id="feedbackTitle" required /></div>
          <div class="form-group full"><label>Pesan</label><textarea class="field" id="feedbackMessage" required></textarea></div>
        </div>
      </div>
      <div class="settings-card-foot"><button class="btn btn-primary" type="submit">Simpan Masukan</button></div>
    </form>
  </div>
  <div class="settings-card"><div class="settings-card-head"><div><h3>Riwayat Masukan</h3><p>Masukan dikirim ke tim pengembang CATAMU.</p></div></div><div class="settings-card-body"><div class="feedback-list" id="feedbackList"></div></div></div>
</div>
