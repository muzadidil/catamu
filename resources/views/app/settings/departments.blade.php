<div id="settingsDepartmentsView" class="settings-subview">
  <div class="settings-subview-head"><button class="icon-btn" type="button" data-setting-back aria-label="Kembali"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><div><p>Kelola departemen yang tersedia sebagai tujuan kunjungan tamu.</p></div></div>

  <div class="department-summary" id="departmentSummary">
    <div class="department-summary-card">
      <div class="department-summary-copy"><span class="department-summary-label">Departemen</span><strong class="department-summary-value" id="departmentTotalStat">0</strong></div>
      <div class="department-summary-icon"><svg viewBox="0 0 24 24"><path d="M4 21V5l8-3 8 3v16"/><path d="M9 8h1m4 0h1M9 12h1m4 0h1M9 16h1m4 0h1M2 21h20"/></svg></div>
    </div>
    <div class="department-summary-card active-card">
      <div class="department-summary-copy"><span class="department-summary-label">Aktif</span><strong class="department-summary-value" id="departmentActiveStat">0</strong></div>
      <div class="department-summary-icon"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></div>
    </div>
    <div class="department-summary-card inactive-card">
      <div class="department-summary-copy"><span class="department-summary-label">Nonaktif</span><strong class="department-summary-value" id="departmentInactiveStat">0</strong></div>
      <div class="department-summary-icon"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></div>
    </div>
  </div>

  <div class="department-workspace">
    <div class="settings-card department-form-card" id="departmentFormCard">
      <div class="settings-card-head">
        <div class="department-form-head">
          <div><h3 id="departmentFormTitle">Tambah Departemen</h3><p>Departemen aktif akan tampil pada Registrasi Tamu.</p></div>
          <span class="department-form-state" id="departmentFormState">Tambah baru</span>
        </div>
      </div>
      <form id="departmentForm">
        <div class="settings-card-body">
          <input type="hidden" id="departmentEditId" />
          <div class="settings-grid">
            <div class="form-group"><label>Nama Departemen</label><input class="field" id="departmentName" required placeholder="Contoh: Keuangan" /></div>
            <div class="form-group"><label>Kode</label><input class="field" id="departmentCode" maxlength="12" placeholder="Contoh: FIN" /></div>
            <div class="form-group full"><label>Keterangan</label><input class="field" id="departmentDescription" placeholder="Keterangan singkat departemen" /></div>
            <div class="form-group full"><div class="switch-row"><div><strong>Status Departemen</strong><small>Hanya departemen aktif yang dapat dipilih saat registrasi.</small></div><label class="toggle"><input type="checkbox" id="departmentActive" checked /><span></span></label></div></div>
          </div>
        </div>
        <div class="settings-card-foot"><button class="btn" type="button" id="resetDepartmentBtn">Reset</button><button class="btn btn-primary" type="submit" id="departmentSaveBtn">Simpan Departemen</button></div>
      </form>
    </div>

    <div class="settings-card department-list-card">
      <div class="settings-card-head"><div><h3>Daftar Departemen</h3><p id="departmentCountText">Belum ada departemen.</p></div></div>
      <div class="department-filter-bar">
        <label class="department-search" aria-label="Cari departemen">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          <input class="field" id="departmentSearchInput" type="search" placeholder="Cari nama, kode, keterangan..." autocomplete="off" />
        </label>
        <select class="field department-status-filter" id="departmentStatusFilter" aria-label="Filter status departemen">
          <option value="">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </select>
        <span class="department-result-count" id="departmentResultCount">0 data</span>
      </div>
      <div class="settings-card-body"><div class="department-list" id="departmentList"></div></div>
    </div>
  </div>
</div>
