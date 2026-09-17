<div class="modal" id="qrisRenewalModal" aria-labelledby="qrisRenewalModalTitle">
  <div class="modal-panel" style="max-width:460px">
    <div class="modal-head"><h3 id="qrisRenewalModalTitle">Pembayaran QRIS</h3><button class="icon-btn" type="button" data-close="qrisRenewalModal" aria-label="Tutup">×</button></div>
    <div class="modal-body">
      <div class="qris-payment-card">
        <div class="qris-payment-summary"><div><span>Paket</span><strong id="qrisPlanName">1 Tahun</strong></div><div><span>Total</span><strong id="qrisPlanTotal">Rp{{ number_format(config('catamu.plans')[0]['amount'], 0, ',', '.') }}</strong></div></div>
        <div class="qris-demo-wrap">
          @if ($state['platform']['qrisImageUrl'])
          <span class="qris-demo-badge">QRIS</span>
          <div class="qris-demo-code has-image"><img src="{{ $state['platform']['qrisImageUrl'] }}" alt="Kode QRIS pembayaran CATAMU" /></div>
          <p class="qris-demo-note">Scan QRIS di atas lalu kirim bukti pembayaran. Status menjadi Menunggu Verifikasi dan masa aktif bertambah setelah disetujui admin CATAMU.</p>
          @else
          <span class="qris-demo-badge">QRIS Belum Tersedia</span>
          <div class="qris-demo-code" role="img" aria-label="Ilustrasi QRIS"></div>
          <p class="qris-demo-note">Gambar QRIS belum diatur oleh admin CATAMU. Hubungi admin untuk informasi pembayaran, lalu kirim bukti pembayaran di bawah.</p>
          @endif
        </div>
        <div class="qris-proof-block">
          <div class="qris-proof-head"><div><strong>Bukti Pembayaran</strong><p>Tambahkan foto atau screenshot bukti pembayaran sebelum konfirmasi.</p></div><span class="required">*</span></div>
          <div class="qris-proof-preview">
            <img id="qrisPaymentProofPreview" alt="Bukti pembayaran QRIS" hidden />
            <div class="qris-proof-empty" id="qrisPaymentProofEmpty"><strong>Belum ada bukti pembayaran</strong><br />Pilih foto atau screenshot pembayaran dari perangkat.</div>
          </div>
          <div class="qris-proof-actions">
            <button class="btn" type="button" id="chooseQrisPaymentProofBtn">Pilih / Ganti Foto</button>
            <button class="btn" type="button" id="removeQrisPaymentProofBtn" hidden>Hapus Foto</button>
          </div>
          <input type="file" id="qrisPaymentProofInput" accept="image/*" hidden />
          <p class="qris-proof-note">Bukti pembayaran dikompresi, dikirim ke server, lalu diverifikasi oleh admin CATAMU. Maksimal file asli 5 MB.</p>
        </div>
      </div>
    </div>
    <div class="modal-foot"><button class="btn" type="button" data-close="qrisRenewalModal">Batal</button><button class="btn btn-primary" type="button" id="confirmQrisRenewalBtn" disabled>Kirim Bukti Pembayaran</button></div>
  </div>
</div>
