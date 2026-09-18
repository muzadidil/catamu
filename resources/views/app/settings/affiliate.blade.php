{{-- Hanya dirender untuk Owner: $state['affiliate'] bernilai null untuk anggota tim. --}}
@if (! empty($state['affiliate']))
<div id="settingsAffiliateView" class="settings-subview">
  <div class="settings-subview-head">
    <button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button>
    <div><p>Ajak kantor lain memakai CATAMU dan dapatkan komisi dari setiap pembayaran mereka.</p></div>
  </div>

  <div class="setting-summary">
    <div class="summary-box"><span>Saldo Komisi</span><strong id="affBalanceStat">Rp0</strong></div>
    <div class="summary-box"><span>Kantor Diajak</span><strong id="affSignupStat">0</strong></div>
    <div class="summary-box"><span>Sudah Berlangganan</span><strong id="affSubscribedStat">0</strong></div>
  </div>

  <div class="settings-card">
    <div class="settings-card-head"><div><h3>Link Undangan</h3><p id="affRateNote">Bagikan link ini. Setiap pembayaran kantor yang Anda ajak memberi Anda komisi.</p></div></div>
    <div class="settings-card-body">
      <div class="aff-link-box">
        <span class="aff-link-value" id="affLinkText">-</span>
        <div class="aff-link-actions">
          <button class="btn btn-compact" type="button" id="affCopyBtn">Salin</button>
          <button class="btn btn-compact" type="button" id="affShareBtn" hidden>Bagikan</button>
        </div>
      </div>
      <p class="hint" id="affAliasNote"></p>

      <label for="affCodeInput">Kode Referral</label>
      <div class="aff-code-row">
        <input class="field" id="affCodeInput" maxlength="24" autocomplete="off" spellcheck="false" placeholder="MISAL: KANTORSAYA" />
        <button class="btn btn-primary" type="button" id="affCodeSaveBtn">Simpan Kode</button>
      </div>
      <p class="hint">4-24 karakter: huruf, angka, dan tanda strip. Mengganti kode membuat link lama berhenti berlaku.</p>

      <div class="aff-meta-row"><span>Link dibuka</span><b id="affVisitStat">0 kali</b></div>
    </div>
  </div>

  <div class="settings-card">
    <div class="settings-card-head"><div><h3>Rekening Pencairan</h3><p>Komisi dikirim ke rekening atau e-wallet ini setelah pengajuan disetujui.</p></div></div>
    <form id="affAccountForm">
      <div class="settings-card-body">
        <label for="affBankInput">Bank / E-wallet</label>
        <input class="field" id="affBankInput" maxlength="40" placeholder="BCA, Mandiri, DANA, ..." />
        <label for="affAccountInput">Nomor Rekening</label>
        <input class="field" id="affAccountInput" maxlength="40" inputmode="numeric" placeholder="1234567890" />
        <label for="affAccountNameInput">Nama Pemilik Rekening</label>
        <input class="field" id="affAccountNameInput" maxlength="120" placeholder="Sesuai buku tabungan" />
      </div>
      <div class="settings-card-foot"><button class="btn btn-primary" type="submit">Simpan Rekening</button></div>
    </form>
  </div>

  <div class="settings-card">
    <div class="settings-card-head"><div><h3>Cairkan Komisi</h3><p id="affPayoutNote">Ajukan pencairan saldo komisi Anda.</p></div></div>
    <form id="affPayoutForm">
      <div class="settings-card-body">
        <label for="affPayoutAmount">Nominal Pencairan</label>
        <input class="field" id="affPayoutAmount" type="number" min="0" step="1000" inputmode="numeric" placeholder="0" />
        <p class="hint" id="affPayoutHint"></p>
        <div class="aff-notice" id="affPayoutPending" hidden></div>
      </div>
      <div class="settings-card-foot"><button class="btn btn-primary" type="submit" id="affPayoutBtn">Ajukan Pencairan</button></div>
    </form>
  </div>

  <div class="settings-card">
    <div class="settings-card-head"><div><h3>Kantor yang Diajak</h3><p>Kantor yang mendaftar lewat link undangan Anda.</p></div></div>
    <div class="settings-card-body"><div class="aff-list" id="affOfficeList"></div></div>
  </div>

  <div class="settings-card">
    <div class="settings-card-head"><div><h3>Riwayat Komisi</h3><p>Komisi yang masuk dari pembayaran kantor yang Anda ajak.</p></div></div>
    <div class="settings-card-body"><div class="aff-list" id="affCommissionList"></div></div>
  </div>

  <div class="settings-card">
    <div class="settings-card-head"><div><h3>Riwayat Pencairan</h3><p>Status pengajuan pencairan komisi Anda.</p></div></div>
    <div class="settings-card-body"><div class="aff-list" id="affPayoutList"></div></div>
  </div>
</div>
@endif
