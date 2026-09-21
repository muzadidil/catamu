(() => {
  const CONFIG = window.CATAMU_CEKIN || {};
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const $ = id => document.getElementById(id);
  const form = $('ckForm');

  function toast(message){
    const el = $('toast');
    el.textContent = message;
    el.classList.add('show');
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => el.classList.remove('show'), 3200);
  }

  function tickClock(){
    const clock = $('ckClock');
    if(!clock) return;
    clock.textContent = new Intl.DateTimeFormat('id-ID', {hour:'2-digit', minute:'2-digit', hour12:CONFIG.timeFormat === '12'}).format(new Date());
  }
  tickClock();
  setInterval(tickClock, 15000);

  if(!form) return;

  /* ---------- Langkah aktif ---------- */
  const stepItems = [...document.querySelectorAll('.ck-steps li')];
  const sections = [...form.querySelectorAll('.ck-section')];
  function markStep(step){
    stepItems.forEach((item, index) => {
      item.classList.toggle('is-active', index + 1 === step);
      item.classList.toggle('is-done', index + 1 < step);
    });
  }
  sections.forEach(section => section.addEventListener('focusin', () => markStep(Number(section.dataset.step))));

  /* ---------- Tujuan kunjungan ---------- */
  const meet = $('ckMeet');
  const meetManual = $('ckMeetManual');
  if(meet){
    meet.addEventListener('change', () => {
      const manual = meet.value === '__manual__';
      meetManual.hidden = !manual;
      meetManual.required = manual;
      if(manual) meetManual.focus();
    });
  }
  document.querySelectorAll('[data-step-people]').forEach(button => button.addEventListener('click', () => {
    const input = $('ckPeople');
    input.value = Math.min(100, Math.max(1, (Number(input.value) || 1) + Number(button.dataset.stepPeople)));
  }));

  /* ---------- Foto ---------- */
  let stream = null;
  let photoData = '';
  const stage = $('ckCameraStage');

  function stopCamera(){
    if(stream){ stream.getTracks().forEach(track => track.stop()); stream = null; }
    if(stage){ $('ckVideo').srcObject = null; stage.classList.remove('camera-on', 'mirror'); }
    if($('ckCaptureBtn')) $('ckCaptureBtn').hidden = true;
  }

  function setPhoto(dataUrl){
    photoData = dataUrl || '';
    if(!stage) return;
    const preview = $('ckPhotoPreview');
    if(photoData){ preview.src = photoData; } else { preview.removeAttribute('src'); }
    stage.classList.toggle('has-photo', !!photoData);
    $('ckRetakeBtn').hidden = !photoData;
    $('ckCameraBtn').hidden = !!photoData;
    $('ckChooseBtn').hidden = !!photoData;
    if(photoData) clearError(stage.closest('.ck-media-item'));
  }

  function compress(source, maxWidth = 720, quality = .78){
    return new Promise(resolve => {
      const img = new Image();
      img.onload = () => {
        const scale = Math.min(1, maxWidth / img.naturalWidth);
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(img.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(img.naturalHeight * scale));
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        resolve(canvas.toDataURL('image/jpeg', quality));
      };
      img.onerror = () => resolve('');
      img.src = source;
    });
  }

  if(stage){
    $('ckCameraBtn').addEventListener('click', async () => {
      if(!navigator.mediaDevices?.getUserMedia){ $('ckPhotoFile').setAttribute('capture', 'user'); $('ckPhotoFile').click(); return; }
      try{
        stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'user'}, width:{ideal:1280}, height:{ideal:960}}, audio:false});
        $('ckVideo').srcObject = stream;
        await $('ckVideo').play().catch(() => {});
        stage.classList.add('camera-on', 'mirror');
        $('ckCaptureBtn').hidden = false;
        $('ckCameraBtn').hidden = true;
      }catch{
        toast('Kamera tidak dapat diakses. Silakan pilih foto dari perangkat.');
        $('ckPhotoFile').setAttribute('capture', 'user');
        $('ckPhotoFile').click();
      }
    });

    $('ckCaptureBtn').addEventListener('click', () => {
      const video = $('ckVideo');
      if(!video.videoWidth) return;
      const scale = Math.min(1, 720 / video.videoWidth);
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(video.videoWidth * scale);
      canvas.height = Math.round(video.videoHeight * scale);
      const ctx = canvas.getContext('2d');
      ctx.translate(canvas.width, 0);
      ctx.scale(-1, 1);
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      stopCamera();
      setPhoto(canvas.toDataURL('image/jpeg', .78));
    });

    $('ckChooseBtn').addEventListener('click', () => { $('ckPhotoFile').removeAttribute('capture'); $('ckPhotoFile').click(); });
    $('ckRetakeBtn').addEventListener('click', () => { setPhoto(''); $('ckPhotoFile').value = ''; });
    $('ckPhotoFile').addEventListener('change', async event => {
      const file = event.target.files?.[0];
      if(!file) return;
      if(!file.type.startsWith('image/')){ toast('File harus berupa gambar.'); return; }
      const reader = new FileReader();
      reader.onload = async () => {
        const data = await compress(String(reader.result));
        if(data){ stopCamera(); setPhoto(data); } else { toast('Foto tidak dapat dibaca.'); }
      };
      reader.readAsDataURL(file);
    });
  }

  /* ---------- Tanda tangan ---------- */
  const canvas = $('ckSignature');
  let drawing = false;
  let hasInk = false;

  function resizeCanvas(){
    if(!canvas) return;
    const rect = canvas.getBoundingClientRect();
    if(!rect.width) return;
    const snapshot = hasInk ? canvas.toDataURL('image/png') : '';
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.round(rect.width * ratio);
    canvas.height = Math.round(rect.height * ratio);
    const ctx = canvas.getContext('2d');
    ctx.lineWidth = 2.4 * ratio;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#111827';
    if(snapshot){
      const img = new Image();
      img.onload = () => ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      img.src = snapshot;
    }
  }

  function point(event){
    const rect = canvas.getBoundingClientRect();
    return {x:(event.clientX - rect.left) * (canvas.width / rect.width), y:(event.clientY - rect.top) * (canvas.height / rect.height)};
  }

  function clearSignature(){
    if(!canvas) return;
    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
    hasInk = false;
    $('ckSignaturePad').classList.remove('has-ink');
  }

  if(canvas){
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    canvas.addEventListener('pointerdown', event => {
      drawing = true;
      hasInk = true;
      $('ckSignaturePad').classList.add('has-ink');
      clearError(canvas.closest('.ck-media-item'));
      try{ canvas.setPointerCapture(event.pointerId); }catch{}
      const ctx = canvas.getContext('2d');
      const p = point(event);
      ctx.beginPath();
      ctx.moveTo(p.x, p.y);
      event.preventDefault();
    });
    canvas.addEventListener('pointermove', event => {
      if(!drawing) return;
      const ctx = canvas.getContext('2d');
      const p = point(event);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
      event.preventDefault();
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(type => canvas.addEventListener(type, () => { drawing = false; }));
    $('ckClearSignature').addEventListener('click', clearSignature);
  }

  /* ---------- Validasi & kirim ---------- */
  function clearError(container){
    if(!container) return;
    container.classList.remove('has-error');
    container.querySelector('.ck-error')?.remove();
  }

  function showError(container, message){
    if(!container) return;
    clearError(container);
    container.classList.add('has-error');
    const note = document.createElement('p');
    note.className = 'ck-error';
    note.textContent = message;
    container.appendChild(note);
  }

  form.addEventListener('input', event => clearError(event.target.closest('.ck-field')));
  $('ckConsent').addEventListener('change', () => $('ckConsent').closest('.ck-consent').classList.remove('has-error'));

  const fieldContainers = {
    name:'ckName', phone:'ckPhone', company:'ckCompany', email:'ckEmail', vehicle:'ckVehicle',
    meet:'ckMeet', departmentId:'ckMeet', purpose:'ckPurpose', people:'ckPeople', notes:'ckNotes',
    photo:'ckCameraStage', signature:'ckSignaturePad'
  };

  function validate(){
    let first = null;
    const fail = (element, message) => {
      const container = element.closest('.ck-field') || element.closest('.ck-media-item');
      showError(container, message);
      first ??= element;
    };
    form.querySelectorAll('.field[required]').forEach(element => {
      if(element.hidden) return;
      if(!element.value.trim()) fail(element, 'Bagian ini wajib diisi.');
      else if(element.type === 'email' && !element.checkValidity()) fail(element, 'Format email tidak valid.');
    });
    const photoItem = stage?.closest('.ck-media-item');
    if(photoItem?.dataset.required === '1' && !photoData) fail(stage, 'Foto wajib diambil.');
    const signItem = canvas?.closest('.ck-media-item');
    if(signItem?.dataset.required === '1' && !hasInk) fail(canvas, 'Tanda tangan wajib diisi.');
    if(!$('ckConsent').checked){
      $('ckConsent').closest('.ck-consent').classList.add('has-error');
      first ??= $('ckConsent');
    }
    if(first){
      first.scrollIntoView({behavior:'smooth', block:'center'});
      toast('Lengkapi data yang ditandai terlebih dahulu.');
      return false;
    }
    return true;
  }

  function payload(){
    const value = name => form.elements[name]?.value?.trim() ?? '';
    const department = value('departmentId');
    return {
      name:value('name'),
      phone:value('phone'),
      company:value('company'),
      email:value('email'),
      vehicle:value('vehicle'),
      departmentId:department && department !== '__manual__' ? department : '',
      meet:department === '__manual__' ? value('meet') : (meet?.selectedOptions[0]?.textContent.replace(/\s\([^)]*\)$/, '') || ''),
      purpose:value('purpose'),
      people:Number(value('people')) || CONFIG.defaultPeople || 1,
      notes:value('notes'),
      photo:photoData,
      signature:hasInk ? canvas.toDataURL('image/png') : '',
      website:value('website')
    };
  }

  /* ---------- Bunyi konfirmasi ---------- */
  // Browser hanya mengizinkan bunyi setelah interaksi pengguna, jadi konteks audio
  // dibuka saat tombol Kirim ditekan dan nadanya baru dimainkan setelah server membalas.
  let audioCtx = null;
  function unlockAudio(){
    try{
      const AudioCtor = window.AudioContext || window.webkitAudioContext;
      if(!AudioCtor) return;
      audioCtx ??= new AudioCtor();
      if(audioCtx.state === 'suspended') audioCtx.resume();
    }catch{}
  }

  function playSuccessChime(){
    if(!audioCtx || audioCtx.state === 'closed') return;
    const begin = audioCtx.currentTime + .03;
    [[659.25, 0], [880, .13], [1318.51, .27]].forEach(([frequency, offset]) => {
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      const at = begin + offset;
      osc.type = 'sine';
      osc.frequency.setValueAtTime(frequency, at);
      gain.gain.setValueAtTime(.0001, at);
      gain.gain.exponentialRampToValueAtTime(.2, at + .02);
      gain.gain.exponentialRampToValueAtTime(.0001, at + .6);
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.start(at);
      osc.stop(at + .65);
    });
  }

  let countdownTimer = 0;
  function showSuccess(name){
    playSuccessChime();
    stopCamera();
    $('toast').classList.remove('show');
    $('ckFormCard').hidden = true;
    $('ckSuccess').hidden = false;
    $('ckSuccessName').textContent = name;
    $('ckSuccessTime').textContent = 'Tercatat ' + new Intl.DateTimeFormat('id-ID', {weekday:'long', day:'2-digit', month:'long', hour:'2-digit', minute:'2-digit', hour12:CONFIG.timeFormat === '12'}).format(new Date());
    window.scrollTo({top:0, behavior:'smooth'});
    let seconds = 20;
    const tick = () => {
      $('ckCountdown').textContent = `Halaman kembali ke form dalam ${seconds} detik.`;
      if(seconds-- <= 0) resetForm();
    };
    tick();
    countdownTimer = setInterval(tick, 1000);
  }

  function resetForm(){
    clearInterval(countdownTimer);
    form.reset();
    if($('ckPeople')) $('ckPeople').value = CONFIG.defaultPeople || 1;
    if(meetManual){ meetManual.hidden = true; meetManual.required = false; }
    form.querySelectorAll('.has-error').forEach(clearError);
    setPhoto('');
    clearSignature();
    markStep(1);
    $('ckSuccess').hidden = true;
    $('ckFormCard').hidden = false;
    setTimeout(resizeCanvas, 30);
    window.scrollTo({top:0, behavior:'smooth'});
  }
  $('ckAgain').addEventListener('click', resetForm);

  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = $('ckSubmit');
    if(button.getAttribute('aria-busy') === 'true') return;
    unlockAudio();
    form.querySelectorAll('.has-error').forEach(clearError);
    if(!validate()) return;

    button.setAttribute('aria-busy', 'true');
    button.querySelector('span').textContent = 'Mengirim...';
    try{
      const response = await fetch(CONFIG.submitUrl, {
        method:'POST',
        credentials:'same-origin',
        headers:{'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF_TOKEN, 'X-Requested-With':'XMLHttpRequest'},
        body:JSON.stringify(payload())
      });
      const data = await response.json().catch(() => ({}));
      if(response.status === 419){ toast('Sesi halaman kedaluwarsa. Halaman akan dimuat ulang.'); setTimeout(() => location.reload(), 1200); return; }
      if(!response.ok){
        if(data.errors){
          let first = null;
          Object.entries(data.errors).forEach(([key, messages]) => {
            const element = $(fieldContainers[key]);
            if(element){ showError(element.closest('.ck-field') || element.closest('.ck-media-item'), messages[0]); first ??= element; }
          });
          first?.scrollIntoView({behavior:'smooth', block:'center'});
        }
        toast(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Cek-in gagal dikirim. Coba lagi.'));
        return;
      }
      showSuccess(data.name || payload().name);
    }catch{
      toast('Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.');
    }finally{
      button.removeAttribute('aria-busy');
      button.querySelector('span').textContent = 'Kirim Cek-in';
    }
  });
})();
