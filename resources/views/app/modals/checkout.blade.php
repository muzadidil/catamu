<div class="modal" id="checkoutModal">
  <div class="modal-panel" style="max-width:480px">
    <div class="modal-head"><h3>Konfirmasi Check-out</h3><button class="icon-btn" data-close="checkoutModal">×</button></div>
    <div class="modal-body">
      <p style="margin-top:0;color:var(--muted)">Tamu akan ditandai telah selesai berkunjung.</p>
      <label>Catatan Check-out</label>
      <textarea class="field" id="checkoutNote" placeholder="Opsional"></textarea>
      <input type="hidden" id="checkoutId" />
    </div>
    <div class="modal-foot"><button class="btn" data-close="checkoutModal">Batal</button><button class="btn btn-success" id="confirmCheckout"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg><span>Check-out</span></button></div>
  </div>
</div>
