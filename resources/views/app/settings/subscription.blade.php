<div id="settingsSubscriptionView" class="settings-subview">
  <div class="settings-subview-head"><button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><div><p>Trial, status pembayaran, dan masa aktif aplikasi.</p></div></div>
  <div class="setting-summary">
    <div class="summary-box"><span>Status</span><strong id="subscriptionStatus">Trial</strong></div>
    <div class="summary-box"><span>Paket</span><strong id="subscriptionPlan">{{ $state['subscription']['plan'] }}</strong></div>
    <div class="summary-box"><span>Berlaku Sampai</span><strong id="subscriptionExpiry">-</strong></div>
  </div>
  <div class="subscription-notice" id="subscriptionNotice"><strong>{{ $state['subscription']['lifetime'] ? 'Paket Lifetime' : 'Trial '.$state['subscription']['trialDays'].' hari' }}</strong><span id="subscriptionNoticeText">Akses penuh tersedia selama masa trial.</span></div>
  <div class="settings-card">
    <div class="settings-card-head"><div><h3>Pilih Paket</h3><p>Pembayaran tidak langsung mengaktifkan paket. Bukti pembayaran masuk ke status Menunggu Verifikasi.</p></div></div>
    <div class="settings-card-body">
      <div class="plan-grid">
        <div class="plan-card"><h4>1 Tahun</h4><div class="price">Rp{{ number_format(config('catamu.plans')[0]['amount'], 0, ',', '.') }}</div><p>Aktif 365 hari setelah pembayaran disetujui. Jika memperpanjang sebelum masa aktif berakhir, hari tambahan dihitung dari tanggal kedaluwarsa saat ini.</p><button class="btn btn-primary" type="button" data-plan-days="365" data-plan-name="1 Tahun">Aktifkan 1 Tahun</button></div>
      </div>
    </div>
  </div>
</div>
