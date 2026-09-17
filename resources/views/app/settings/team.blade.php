<div id="settingsTeamView" class="settings-subview">
  <div class="settings-subview-head"><button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><div><p>Kelola anggota, role, status, dan hak akses tim.</p></div></div>

  <div class="team-workspace">
    <div class="settings-card team-list-card">
      <div class="settings-card-head">
        <div><h3>Daftar Tim</h3><p>Kelola anggota, role, status, dan hak akses.</p></div>
        <div class="team-list-head-actions">
          <span class="team-header-count" id="teamCountText">Belum ada anggota.</span>
          <button class="btn btn-primary" type="button" id="addTeamBtn" aria-haspopup="dialog" aria-controls="teamModal"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Tambah Anggota</span></button>
        </div>
      </div>
      <div class="team-filter-bar">
        <label class="team-search" aria-label="Cari anggota"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input class="field" id="teamSearchInput" type="search" placeholder="Cari nama, email, nomor HP..." autocomplete="off" /></label>
        <select class="field team-role-filter" id="teamRoleFilter" aria-label="Filter role"><option value="">Semua Role</option><option>Admin</option><option>Resepsionis</option><option>Viewer</option></select>
        <select class="field team-status-filter" id="teamStatusFilter" aria-label="Filter status"><option value="">Semua Status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>
        <span class="team-result-count" id="teamResultCount">0 data</span>
      </div>
      <div class="settings-card-body team-list-body">
        <div class="team-list-head" aria-hidden="true">
          <div>Anggota</div><div>Role</div><div>Status</div><div>Password</div><div>Hak Akses</div><div class="team-list-head-action">Aksi</div>
        </div>
        <div class="team-list" id="teamList"></div>
      </div>
    </div>
  </div>
</div>
