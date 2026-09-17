<div id="settingsRatingView" class="settings-subview">
  <div class="settings-subview-head"><button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><div><p>Nilai pengalaman penggunaan aplikasi.</p></div></div>
  <div class="settings-card">
    <form id="ratingForm">
      <div class="settings-card-body">
        <label>Pilih Rating</label>
        <div class="rating-stars" id="ratingStars">
          <button class="star-btn" type="button" data-rating="1">★</button><button class="star-btn" type="button" data-rating="2">★</button><button class="star-btn" type="button" data-rating="3">★</button><button class="star-btn" type="button" data-rating="4">★</button><button class="star-btn" type="button" data-rating="5">★</button>
        </div>
        <label>Komentar</label><textarea class="field" id="ratingComment" placeholder="Ceritakan pengalaman Anda..."></textarea>
        <div class="hint" id="ratingSavedText">Belum ada rating tersimpan.</div>
      </div>
      <div class="settings-card-foot"><button class="btn btn-primary" type="submit">Simpan Rating</button></div>
    </form>
  </div>
</div>
