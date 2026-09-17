<section class="page" id="page-guests">
  <div class="print-title">
    <h1 id="printOffice">BUKU TAMU KANTOR</h1>
    <p id="printDate"></p>
  </div>
  <div class="section-title">
    <div><p>Kelola seluruh data kunjungan kantor.</p></div>
    <button class="btn btn-primary" data-go="form"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Tambah Tamu</span></button>
  </div>
  <div class="card guest-list-card">
    <div class="card-head">
      <div class="guest-search-row">
        <div class="search-wrap"><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg></span><input id="searchInput" placeholder="Cari nama, instansi, tujuan, atau nomor HP..." autocomplete="off" /></div>
        <div class="guest-list-actions">
          <button class="btn" id="exportBtn" title="Ekspor daftar tamu ke CSV"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"/><path d="m7.5 10.5 4.5 4.5 4.5-4.5"/><path d="M4 21h16"/></svg><span>CSV</span></button>
          <button class="btn" id="printBtn" title="Cetak daftar tamu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V3h10v5"/><rect x="5" y="14" width="14" height="7" rx="1"/><path d="M5 17H3V10a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7h-2"/><path d="M17 11h.01"/></svg><span>Cetak</span></button>
        </div>
      </div>
      <div class="guest-filter-row">
        <div class="guest-filter-controls">
          <label class="guest-filter-control" for="dateFilter"><span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/></svg> Tanggal</span><input class="field" type="date" id="dateFilter" /></label>
          <label class="guest-filter-control" for="statusFilter"><span><i class="filter-status-dot"></i> Status</span><select class="field" id="statusFilter">
            <option value="">Semua</option>
            <option value="in">Sedang Berkunjung</option>
            <option value="out">Selesai</option>
          </select></label>
        </div>
        <div class="guest-filter-meta">
          <span class="guest-result-count" id="guestResultCount">0 data</span>
          <button class="btn btn-ghost btn-compact" id="resetGuestFilters" type="button"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7v5h5"/><path d="M5.5 16a8 8 0 1 0 .2-8.2L4 12"/></svg><span>Reset Filter</span></button>
        </div>
      </div>
    </div>
    <div class="table-wrap guest-desktop-table">
      <table class="guest-table">
        <thead><tr><th class="col-no">No.</th><th>Tamu</th><th>Instansi</th><th>Keperluan</th><th>Bertemu</th><th class="col-time">Masuk</th><th class="col-time">Keluar</th><th class="col-status">Status</th><th class="col-actions">Aksi</th></tr></thead>
        <tbody id="guestBody"></tbody>
      </table>
    </div>
    <div class="guest-mobile-list" id="guestMobileList"></div>
    <div class="empty" id="guestEmpty"><div class="empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg></div><strong>Data tamu tidak ditemukan</strong><span>Ubah pencarian atau filter yang digunakan.</span></div>
  </div>
</section>
