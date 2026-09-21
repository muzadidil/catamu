(() => {
  const $ = id => document.getElementById(id);

  /* ---------- Toast ---------- */
  const toastEl = $('adToast');
  function toast(message){
    if(!message) return;
    toastEl.textContent = message;
    toastEl.classList.add('is-visible');
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => toastEl.classList.remove('is-visible'), 3600);
  }
  if(toastEl.textContent.trim()) setTimeout(() => toast(toastEl.textContent.trim()), 80);

  /* ---------- Dialog konfirmasi untuk form[data-confirm] ---------- */
  const dialog = $('adConfirm');
  const okButton = $('adConfirmOk');
  const typeInput = $('adConfirmType');
  const noteInput = $('adConfirmNote');
  const variants = {danger:'ad-btn--danger', success:'ad-btn--success', accent:'ad-btn--accent', primary:'ad-btn--primary'};
  let pendingForm = null;

  function syncTypedConfirmation(){
    const word = pendingForm?.dataset.confirmType;
    okButton.disabled = !!word && typeInput.value.trim() !== word;
  }

  document.addEventListener('submit', event => {
    const form = event.target;
    if(!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
    if(form.dataset.confirmed === '1'){ delete form.dataset.confirmed; return; }
    if(typeof dialog.showModal !== 'function') return;
    event.preventDefault();

    pendingForm = form;
    const variant = form.dataset.confirmVariant || 'primary';
    dialog.dataset.variant = variant;
    $('adConfirmTitle').textContent = form.dataset.confirmTitle || 'Lanjutkan tindakan ini?';
    $('adConfirmMessage').textContent = form.dataset.confirmMessage || '';
    okButton.textContent = form.dataset.confirmLabel || 'Lanjutkan';
    okButton.className = `ad-btn ${variants[variant] || variants.primary}`;

    $('adConfirmNoteWrap').hidden = !form.dataset.confirmNote;
    noteInput.value = '';
    $('adConfirmTypeWrap').hidden = !form.dataset.confirmType;
    $('adConfirmTypeWord').textContent = form.dataset.confirmType || '';
    typeInput.value = '';
    syncTypedConfirmation();

    dialog.showModal();
    setTimeout(() => (form.dataset.confirmType ? typeInput : form.dataset.confirmNote ? noteInput : okButton).focus(), 30);
  });

  typeInput.addEventListener('input', syncTypedConfirmation);

  dialog.addEventListener('close', () => {
    const form = pendingForm;
    pendingForm = null;
    if(!form || dialog.returnValue !== 'ok') return;
    if(form.dataset.confirmNote){
      const field = form.querySelector(`[name="${form.dataset.confirmNote}"]`);
      if(field) field.value = noteInput.value.trim();
    }
    const typedTarget = form.querySelector('[data-confirm-type-target]');
    if(typedTarget) typedTarget.value = typeInput.value.trim();
    form.dataset.confirmed = '1';
    form.querySelectorAll('button[type="submit"]').forEach(button => button.setAttribute('aria-busy', 'true'));
    form.requestSubmit ? form.requestSubmit() : form.submit();
  });

  /* ---------- Lightbox bukti pembayaran ---------- */
  const lightbox = $('adLightbox');
  document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-lightbox]');
    if(trigger && typeof lightbox.showModal === 'function'){
      event.preventDefault();
      $('adLightboxImage').src = trigger.getAttribute('href');
      $('adLightboxOpen').href = trigger.getAttribute('href');
      lightbox.showModal();
      return;
    }
    if(event.target.closest('[data-lightbox-close]') || event.target === lightbox) lightbox.close();
  });

  /* ---------- Tema terang / gelap ---------- */
  const themeRoot = document.documentElement;
  const themeButton = document.querySelector('[data-theme-toggle]');
  const systemDark = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
  const savedTheme = () => {
    try{ const value = localStorage.getItem('adTheme'); return value === 'light' || value === 'dark' ? value : null; }catch{ return null; }
  };
  function applyTheme(theme){
    themeRoot.setAttribute('data-theme', theme);
    if(!themeButton) return;
    const label = theme === 'dark' ? 'Gunakan tema terang' : 'Gunakan tema gelap';
    themeButton.setAttribute('aria-label', label);
    themeButton.title = label;
  }
  applyTheme(themeRoot.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
  themeButton?.addEventListener('click', () => {
    const next = themeRoot.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    try{ localStorage.setItem('adTheme', next); }catch{}
  });
  systemDark?.addEventListener?.('change', event => { if(!savedTheme()) applyTheme(event.matches ? 'dark' : 'light'); });

  /* ---------- Pengaturan trial ---------- */
  const trialInput = document.querySelector('[data-trial-input]');
  const trialPreview = document.querySelector('[data-trial-preview]');
  if(trialInput && trialPreview){
    const formatter = new Intl.DateTimeFormat('id-ID', {day:'2-digit', month:'long', year:'numeric'});
    const today = new Date(trialPreview.dataset.today + 'T00:00:00');
    const update = () => {
      const days = Math.min(365, Math.max(1, Number(trialInput.value) || 1));
      const until = new Date(today);
      until.setDate(until.getDate() + days);
      trialPreview.textContent = formatter.format(until);
      document.querySelectorAll('[data-trial-preset]').forEach(chip => chip.classList.toggle('is-active', Number(chip.dataset.trialPreset) === Number(trialInput.value)));
    };
    trialInput.addEventListener('input', update);
    document.querySelectorAll('[data-trial-preset]').forEach(chip => chip.addEventListener('click', () => { trialInput.value = chip.dataset.trialPreset; update(); }));
  }

  /* ---------- Unggah QRIS ---------- */
  const dropzone = document.querySelector('[data-dropzone]');
  if(dropzone){
    const input = dropzone.querySelector('input[type="file"]');
    const preview = dropzone.querySelector('[data-dropzone-preview]');
    const empty = dropzone.querySelector('[data-dropzone-empty]');
    input.addEventListener('change', () => {
      const file = input.files?.[0];
      if(!file) return;
      if(file.size > 2 * 1024 * 1024){ toast('Ukuran gambar QRIS maksimal 2 MB.'); input.value = ''; return; }
      preview.src = URL.createObjectURL(file);
      preview.hidden = false;
      empty.hidden = true;
      dropzone.classList.add('has-image');
    });
    ['dragenter', 'dragover'].forEach(type => dropzone.addEventListener(type, () => dropzone.classList.add('is-dragging')));
    ['dragleave', 'drop'].forEach(type => dropzone.addEventListener(type, () => dropzone.classList.remove('is-dragging')));
  }
})();
