<section class="page" id="page-form">
  <div class="card guest-form-card">
    <form id="guestForm">
      <div class="guest-form-body">
        <input type="hidden" id="editId" />
        <input type="hidden" id="guestPhotoData" />
        <input type="hidden" id="signatureData" />

        <section class="form-section">
          <div class="form-section-head">
            <div class="form-section-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/></svg></div>
            <div><div class="form-section-title">Identitas Tamu</div><p>Data dasar pengunjung.</p></div>
          </div>
          <div class="form-grid">
            <div class="form-group" data-guest-field="name"><label>Nama Lengkap <span class="required" data-required-marker>*</span></label><input class="field" id="name" required placeholder="Masukkan nama tamu" /></div>
            <div class="form-group" data-guest-field="phone"><label>Nomor HP / WhatsApp <span class="required" data-required-marker>*</span></label><input class="field" id="phone" required inputmode="tel" placeholder="Contoh: 081234567890" /></div>
            <div class="form-group" data-guest-field="company"><label>Instansi / Perusahaan <span class="required" data-required-marker hidden>*</span></label><input class="field" id="company" placeholder="Nama instansi atau perusahaan" /></div>
            <div class="form-group" data-guest-field="email"><label>Email <span class="required" data-required-marker hidden>*</span></label><input class="field" id="email" type="email" inputmode="email" autocomplete="email" placeholder="nama@email.com" /></div>
            <div class="form-group" data-guest-field="vehicle"><label>Plat Nomor <span class="required" data-required-marker hidden>*</span></label><input class="field" id="vehicle" autocomplete="off" autocapitalize="characters" placeholder="Contoh: N 1234 AB" /></div>
          </div>
        </section>

        <section class="form-section" data-guest-section="visit">
          <div class="form-section-head">
            <div class="form-section-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M8 9h8M8 13h8M8 17h5"/></svg></div>
            <div><div class="form-section-title">Tujuan Kunjungan</div><p>Catat pihak yang ditemui dan keperluan tamu.</p></div>
          </div>
          <div class="form-grid">
            <div class="form-group" data-guest-field="meet">
              <label>Orang / Bagian yang Ditemui <span class="required" data-required-marker>*</span></label>
              <select class="field" id="meetDepartment" required>
                <option value="">Pilih departemen</option>
                <option value="__manual__">Lainnya / Tulis Manual</option>
              </select>
              <input class="field" id="meetManual" placeholder="Nama orang atau bagian yang ditemui" style="margin-top:8px;display:none" />
              <div class="hint">Daftar departemen dapat dikelola dari Pengaturan → Departemen.</div>
            </div>
            <div class="form-group" data-guest-field="people"><label>Jumlah Pengunjung <span class="required" data-required-marker hidden>*</span></label><input class="field" id="people" type="number" min="1" value="1" /></div>
            <div class="form-group full" data-guest-field="purpose"><label>Keperluan Kunjungan <span class="required" data-required-marker>*</span></label><textarea class="field" id="purpose" required placeholder="Jelaskan keperluan kunjungan"></textarea></div>
            <div class="form-group" data-guest-field="notes"><label>Catatan <span class="required" data-required-marker hidden>*</span></label><input class="field" id="notes" placeholder="Catatan tambahan jika diperlukan" /></div>
          </div>
        </section>

        <div class="form-media-grid">
          <section class="form-section" data-guest-field="photo">
            <div class="form-section-head">
              <div class="form-section-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h4l1.5-2h5L16 7h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="4"/></svg></div>
              <div><div class="form-section-title">Foto Tamu <span class="required" data-required-marker hidden>*</span></div><p>Pilih kamera depan/belakang lalu ambil foto.</p></div>
            </div>
            <div class="camera-box">
              <div class="camera-stage" id="cameraStage">
                <video id="cameraPreview" autoplay muted playsinline></video>
                <img id="photoPreview" alt="Foto tamu" />
                <div class="camera-placeholder" id="cameraPlaceholder"><strong>Belum ada foto</strong>Aktifkan kamera atau pilih foto dari perangkat.</div>
              </div>
              <div class="camera-toolbar">
                <button class="btn" type="button" id="cameraFrontBtn"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/></svg><span>Kamera Depan</span></button>
                <button class="btn" type="button" id="cameraBackBtn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h4l1.5-2h5L16 7h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="4"/></svg><span>Kamera Belakang</span></button>
                <button class="btn btn-primary wide" type="button" id="capturePhotoBtn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h4l1.5-2h5L16 7h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="4"/></svg><span>Ambil Foto</span></button>
                <button class="btn" type="button" id="retakePhotoBtn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7v5h5"/><path d="M5.5 16a8 8 0 1 0 .2-8.2L4 12"/></svg><span>Foto Ulang</span></button>
                <button class="btn" type="button" id="choosePhotoBtn"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="2"/><path d="m4 18 5-5 4 4 2-2 5 4"/></svg><span>Pilih Foto</span></button>
              </div>
              <input type="file" id="photoFileInput" accept="image/*" hidden />
              <p class="media-note">Kamera browser membutuhkan izin perangkat. Bila kamera browser tidak tersedia, aplikasi akan memakai kamera/pemilih foto bawaan perangkat.</p>
            </div>
          </section>

          <section class="form-section" data-guest-field="signature">
            <div class="form-section-head">
              <div class="form-section-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 19c3-1 4-4 6-7 1.2-1.8 2.5-4 4.1-3 1.7 1.1-1.5 6.4-.3 7.3 1 .8 2.5-2.7 4-2.1 1.2.5.3 2.3 1.7 2.6.8.2 1.6-.2 2.5-.8"/><path d="M3 21h18"/></svg></div>
              <div><div class="form-section-title">Tanda Tangan <span class="required" data-required-marker hidden>*</span></div><p>Tamu dapat menandatangani langsung dengan jari atau mouse.</p></div>
            </div>
            <div class="signature-wrap">
              <div class="signature-pad" id="signaturePad">
                <canvas id="signatureCanvas"></canvas>
                <div class="signature-hint">Tanda tangan di area ini</div>
              </div>
              <div class="signature-actions">
                <button class="btn" type="button" id="clearSignatureBtn">Hapus TTD</button>
              </div>
              <p class="media-note">Tanda tangan disimpan bersama data tamu di server.</p>
            </div>
          </section>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn" id="resetFormBtn">Reset</button>
        <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg><span>Simpan Data Tamu</span></button>
      </div>
    </form>
  </div>
</section>
