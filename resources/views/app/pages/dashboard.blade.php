<section class="page active" id="page-dashboard">
  <div class="grid-stats">
    <div class="stat-card">
      <div><div class="stat-label">Tamu Hari Ini</div><div class="stat-value" id="statToday">0</div><div class="stat-note">Registrasi hari ini</div></div>
      <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 20v-1.5A5.5 5.5 0 0 1 9 13h1"/><path d="M15.5 6a2.8 2.8 0 0 1 0 5.3"/><path d="M15 13.3a5 5 0 0 1 5.5 5V20"/></svg></div>
    </div>
    <div class="stat-card">
      <div><div class="stat-label">Sedang Berkunjung</div><div class="stat-value" id="statInside">0</div><div class="stat-note">Belum check-out</div></div>
      <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8l4 4-4 4"/><path d="M18 12H8"/><path d="M11 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6"/></svg></div>
    </div>
    <div class="stat-card">
      <div><div class="stat-label">Selesai</div><div class="stat-value" id="statDone">0</div><div class="stat-note">Check-out hari ini</div></div>
      <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.7 2.7L16.5 9"/></svg></div>
    </div>
    <div class="stat-card">
      <div><div class="stat-label">Bulan Ini</div><div class="stat-value" id="statMonth">0</div><div class="stat-note">Total kunjungan</div></div>
      <div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/></svg></div>
    </div>
  </div>

  <div class="dashboard-layout">
    <div class="card dashboard-card">
      <div class="card-head">
        <div><h3>Tamu Terbaru</h3><p>Kunjungan paling baru</p></div>
        <button class="btn btn-ghost" data-go="guests">Lihat Semua</button>
      </div>
      <div class="table-wrap dashboard-recent-table">
        <table>
          <thead><tr><th>Tamu</th><th>Tujuan</th><th>Waktu Masuk</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody id="recentBody"></tbody>
        </table>
      </div>
      <div class="recent-mobile-list" id="recentMobileList"></div>
      <div class="empty" id="recentEmpty"><div class="empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg></div><strong>Belum ada data tamu</strong><span>Kunjungan terbaru akan tampil di sini.</span></div>
    </div>

    <div class="dashboard-side">
      <div class="card dashboard-card">
        <div class="card-head"><div><h3>Aktivitas Terbaru</h3><p>Check-in dan check-out</p></div></div>
        <div class="card-body"><div class="activity-list dashboard-timeline" id="activityList"></div></div>
      </div>
    </div>
  </div>
</section>
