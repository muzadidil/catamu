<div class="modal" id="deleteAccountModal" aria-labelledby="deleteAccountModalTitle">
  <div class="modal-panel" style="max-width:520px">
    <div class="modal-head"><h3 id="deleteAccountModalTitle">Hapus Akun</h3><button class="icon-btn" data-close="deleteAccountModal" aria-label="Tutup">×</button></div>
    <div class="modal-body">
      <div class="delete-account-warning"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5M12 18h.01"/></svg><div><strong>Tindakan ini tidak dapat dibatalkan</strong><p>Akun kantor, anggota tim, dan seluruh data di server akan dihapus permanen.</p></div></div>
      <label class="delete-account-confirm-label" for="deleteAccountConfirmText">Ketik <code>HAPUS AKUN</code> untuk melanjutkan</label>
      <input class="field" id="deleteAccountConfirmText" autocomplete="off" spellcheck="false" placeholder="HAPUS AKUN" />
    </div>
    <div class="modal-foot"><button class="btn" type="button" data-close="deleteAccountModal">Batal</button><button class="btn btn-danger" type="button" id="confirmDeleteAccountBtn" disabled>Hapus Akun &amp; Data</button></div>
  </div>
</div>
