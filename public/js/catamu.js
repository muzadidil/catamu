(() => {
  const BOOT = window.CATAMU_STATE || {};
  const APP_URL = (document.querySelector('meta[name="app-url"]')?.content || '').replace(/\/+$/,'');
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const POLL_INTERVAL = 30000;


  /* FLOW CORE START */
  const CATAMU_FLOW = (()=>{
    const DAY=86400000;
    const asMs=value=>{ const ms=value?new Date(value).getTime():0; return Number.isFinite(ms)?ms:0; };
    function normalizeSubscription(source={},now=Date.now()){
      const raw=source && typeof source==='object' ? {...source} : {};
      const hadLegacyAccess=!!raw.activatedAt || !!raw.expiresAt;
      let trialStartedAt=raw.trialStartedAt;
      let trialExpiresAt=raw.trialExpiresAt;
      if(!trialStartedAt){
        const start=hadLegacyAccess ? (asMs(raw.activatedAt)||now) : now;
        trialStartedAt=new Date(start).toISOString();
      }
      if(!trialExpiresAt){
        const start=asMs(trialStartedAt)||now;
        trialExpiresAt=new Date(hadLegacyAccess ? start : start+(3*DAY)).toISOString();
      }
      const normalized={
        plan:raw.plan||'Trial 3 Hari',
        status:raw.status||'trial',
        trialStartedAt,
        trialExpiresAt,
        activatedAt:raw.activatedAt||null,
        expiresAt:raw.expiresAt||null,
        pendingPayment:raw.pendingPayment||null,
        ...raw,
        trialStartedAt,
        trialExpiresAt
      };
      const state=subscriptionState(normalized,now);
      if(state==='active'||state==='active-pending') normalized.status='active';
      else if(state==='trial'||state==='trial-pending') normalized.status=normalized.pendingPayment?'pending':'trial';
      else if(state==='pending') normalized.status='pending';
      else normalized.status='expired';
      if(!normalized.plan || normalized.plan==='Free Offline') normalized.plan=normalized.activatedAt?'1 Tahun':'Trial 3 Hari';
      return normalized;
    }
    function subscriptionState(source={},now=Date.now()){
      const activeUntil=asMs(source.expiresAt);
      const trialUntil=asMs(source.trialExpiresAt);
      const pending=!!source.pendingPayment || source.status==='pending';
      if(source.lifetime) return pending?'active-pending':'active';
      if(activeUntil>now) return pending?'active-pending':'active';
      if(!source.activatedAt && trialUntil>now) return pending?'trial-pending':'trial';
      if(pending) return 'pending';
      return 'expired';
    }
    function subscriptionWritable(source={},now=Date.now()){
      return ['trial','trial-pending','active','active-pending'].includes(subscriptionState(source,now));
    }
    function roleAccess(actor={}){
      if(!actor || actor.type==='owner') return {guestsView:true,guestsWrite:true,reports:true,settings:true,teamManage:true,subscriptionManage:true,accountManage:true};
      const role=String(actor.role||'Resepsionis');
      const permissions=actor.permissions||{};
      const viewer=role==='Viewer';
      const admin=role==='Admin';
      return {
        guestsView:!!permissions.guests,
        guestsWrite:!!permissions.guests && !viewer,
        reports:!!permissions.reports,
        settings:!!permissions.settings && !viewer,
        teamManage:admin && !!permissions.settings,
        subscriptionManage:false,
        accountManage:false
      };
    }
    return {DAY,normalizeSubscription,subscriptionState,subscriptionWritable,roleAccess};
  })();
  /* FLOW CORE END */

  const guestFieldDefaults = {
    name:{visible:true,required:true,locked:true},
    phone:{visible:true,required:true},
    company:{visible:true,required:false},
    email:{visible:true,required:false},
    vehicle:{visible:true,required:false},
    meet:{visible:true,required:true},
    people:{visible:true,required:false},
    purpose:{visible:true,required:true},
    notes:{visible:true,required:false},
    photo:{visible:true,required:false},
    signature:{visible:true,required:false}
  };

  const defaults = {
    office: 'Kantor Utama',
    address: 'Alamat kantor belum diatur',
    hours: '08.00–17.00 WIB',
    officer: 'Resepsionis',
    phone: '',
    email: '',
    theme: 'system',
    dateFormat: 'long',
    timeFormat: '24',
    defaultPeople: 1,
    guestVoiceEnabled: true
  };
  const profileDefaults = {name:'Administrator',email:'',phone:'',hasPin:false,photo:''};
  const subscriptionDefaults = {plan:'Trial 3 Hari',status:'trial',trialStartedAt:null,trialExpiresAt:null,expiresAt:null,activatedAt:null,pendingPayment:null};
  const notificationDefaults = {inApp:true,browser:false,checkIn:true,checkOut:true,updates:true};

  let guests = Array.isArray(BOOT.guests) ? BOOT.guests : [];
  let settings = {...defaults, ...(BOOT.settings||{})};
  settings.guestFields=normalizeGuestFieldSettings(settings.guestFields);
  let profile = {...profileDefaults, ...(BOOT.profile||{})};
  let actor = BOOT.actor || {type:'owner',name:profile.name,role:'Owner',permissions:{guests:true,reports:true,settings:true}};
  let team = Array.isArray(BOOT.team) ? BOOT.team : [];
  let departments = Array.isArray(BOOT.departments) ? BOOT.departments : [];
  let subscription = CATAMU_FLOW.normalizeSubscription({...subscriptionDefaults, ...(BOOT.subscription||{})});
  let pendingRenewal = null;
  let qrisPaymentProofData = '';
  let feedbacks = Array.isArray(BOOT.feedbacks) ? BOOT.feedbacks : [];
  let rating = {score:0,comment:'',updatedAt:null,...(BOOT.rating||{})};
  let notifications = Array.isArray(BOOT.notifications) ? BOOT.notifications : [];
  let notificationPrefs = {...notificationDefaults, ...(BOOT.notificationPrefs||{})};
  const seenNotificationIds = new Set(notifications.map(n=>n.id));
  let localMutationCount = 0;
  let deferredInstallPrompt=null;
  let pwaRegistrationFailed=false;
  let selectedRating = Number(rating.score)||0;
  let cameraStream = null;
  let currentFacingMode = 'user';
  let signatureDrawing = false;
  let signatureHasInk = false;
  let currentDetailGuestId=null;

  const $ = id => document.getElementById(id);
  const els = {
    sidebar:$('sidebar'), overlay:$('overlay'), headerTitle:$('headerTitle'), headerSubtitle:$('headerSubtitle'),
    guestBody:$('guestBody'), guestEmpty:$('guestEmpty'), recentBody:$('recentBody'), recentEmpty:$('recentEmpty'),
    activityList:$('activityList'), toast:$('toast')
  };

  function isStandaloneMode(){
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone===true;
  }

  function isPwaSecureContext(){
    const localHost=location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    return location.protocol === 'https:' || (location.protocol === 'http:' && localHost);
  }

  function renderPwaInstallState(){
    const status=$('pwaInstallStatus');
    const note=$('pwaInstallNote');
    const btn=$('installPwaBtn');
    if(!status||!note||!btn) return;

    status.className='pwa-status-pill';
    btn.disabled=true;

    if(isStandaloneMode()){
      status.textContent='Sudah Terpasang';
      status.classList.add('installed');
      note.textContent='Aplikasi sedang berjalan dalam mode standalone.';
      return;
    }
    if(!isPwaSecureContext()){
      status.textContent='Perlu HTTPS/localhost';
      note.textContent='Mode PWA tidak aktif jika HTML dibuka langsung dari file://.';
      return;
    }
    if(pwaRegistrationFailed){
      status.textContent='Tidak tersedia';
      note.textContent='Service Worker gagal didaftarkan. Aplikasi utama tetap dapat digunakan.';
      return;
    }
    if(deferredInstallPrompt){
      status.textContent='Siap Diinstal';
      status.classList.add('ready');
      note.textContent='Browser mendukung instalasi aplikasi pada perangkat ini.';
      btn.disabled=false;
      return;
    }
    status.textContent='Tidak tersedia';
    note.textContent='Gunakan menu browser untuk Add to Home Screen jika opsi instalasi tersedia.';
  }

  async function registerPwaServiceWorker(){
    if(!isPwaSecureContext() || !('serviceWorker' in navigator)){
      renderPwaInstallState();
      return;
    }
    try{
      await navigator.serviceWorker.register(APP_URL+'/service-worker.js');
    }catch(error){
      pwaRegistrationFailed=true;
      console.warn('PWA Service Worker gagal didaftarkan:',error);
    }
    renderPwaInstallState();
  }

  async function installPwaApp(){
    if(!deferredInstallPrompt){
      renderPwaInstallState();
      toast('Instalasi otomatis belum tersedia. Gunakan menu browser jika ada.');
      return;
    }
    await deferredInstallPrompt.prompt();
    const prompt=deferredInstallPrompt;
    deferredInstallPrompt=null;
    await prompt.userChoice.catch(()=>null);
    renderPwaInstallState();
  }

  function uiIcon(name,size=18){
    const icons={
      eye:'<circle cx="11" cy="11" r="3"/><path d="M2.5 11s3.5-6 8.5-6 8.5 6 8.5 6-3.5 6-8.5 6-8.5-6-8.5-6Z"/>',
      edit:'<path d="M4 20h4l11-11-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
      checkout:'<path d="M14 8l4 4-4 4"/><path d="M18 12H8"/><path d="M11 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6"/>',
      trash:'<path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6M14 11v6"/>',
      check:'<circle cx="12" cy="12" r="9"/><path d="m8 12 2.7 2.7L16.5 9"/>',
      sun:'<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
      moon:'<path d="M20.5 14.3A8.5 8.5 0 0 1 9.7 3.5 8.5 8.5 0 1 0 20.5 14.3Z"/>'
    };
    return `<svg class="ui-icon" width="${size}" height="${size}" viewBox="0 0 24 24" aria-hidden="true">${icons[name]||''}</svg>`;
  }

  function guestIdentityMarkup(g){
    const visual=g.photo
      ? `<div class="avatar guest-avatar-photo"><img src="${esc(g.photo)}" alt="" loading="lazy"></div>`
      : `<div class="avatar">${esc(initials(g.name))}</div>`;
    return `${visual}<div class="guest-identity-copy"><div class="guest-name">${esc(g.name)}</div><div class="subtext">${esc(g.phone||'')}${g.company?` • ${esc(g.company)}`:''}</div></div>`;
  }

  class ApiError extends Error{
    constructor(message,status=0,silent=false){ super(message); this.status=status; this.silent=silent; }
  }

  async function api(method,path,body){
    if(method!=='GET') localMutationCount++;
    let response;
    try{
      response=await fetch(APP_URL+path,{
        method,
        credentials:'same-origin',
        headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'X-Requested-With':'XMLHttpRequest'},
        body:body===undefined?undefined:JSON.stringify(body)
      });
    }catch{
      throw new ApiError('Tidak dapat terhubung ke server. Periksa koneksi lalu coba lagi.');
    }
    const data=await response.json().catch(()=>({}));
    if(response.status===401 || response.status===419){
      toast('Sesi berakhir. Silakan masuk kembali.');
      setTimeout(()=>location.reload(),900);
      throw new ApiError('Sesi berakhir.',response.status,true);
    }
    if(!response.ok){
      const firstError=data.errors ? Object.values(data.errors).flat()[0] : '';
      throw new ApiError(firstError||data.message||'Terjadi kesalahan pada server.',response.status);
    }
    return data;
  }

  function reportApiError(error){
    if(error?.silent) return;
    if(!(error instanceof ApiError)) console.error(error);
    toast(error?.message||'Terjadi kesalahan.');
  }

  async function busy(button,task){
    if(!button) return task();
    if(button.dataset.busy==='1') return;
    button.dataset.busy='1';
    button.setAttribute('aria-busy','true');
    try{ return await task(); }
    finally{ delete button.dataset.busy; button.removeAttribute('aria-busy'); }
  }

  function readFileAsDataUrl(file){
    return new Promise((resolve,reject)=>{
      const reader=new FileReader();
      reader.onload=()=>resolve(String(reader.result||''));
      reader.onerror=()=>reject(new ApiError('File tidak dapat dibaca.'));
      reader.readAsDataURL(file);
    });
  }
  function normalizeGuestFieldSettings(source){
    const raw=source && typeof source==='object' ? source : {};
    const normalized={};
    Object.entries(guestFieldDefaults).forEach(([key,base])=>{
      const current=raw[key] && typeof raw[key]==='object' ? raw[key] : {};
      const visible=base.locked ? true : (Object.hasOwn(current,'visible') ? current.visible!==false : base.visible);
      const required=base.locked ? true : (visible && (Object.hasOwn(current,'required') ? current.required===true : base.required));
      normalized[key]={visible,required};
      if(base.locked) normalized[key].locked=true;
    });
    return normalized;
  }

  function isGuestFieldVisible(key){
    return settings.guestFields?.[key]?.visible!==false;
  }

  function isGuestFieldRequired(key){
    return isGuestFieldVisible(key) && settings.guestFields?.[key]?.required===true;
  }

  function renderGuestFieldSettingsForm(){
    const config=normalizeGuestFieldSettings(settings.guestFields);
    document.querySelectorAll('[data-guest-field-setting]').forEach(row=>{
      const key=row.dataset.guestFieldSetting;
      const field=config[key]||guestFieldDefaults[key];
      const visible=row.querySelector('[data-field-visible]');
      const required=row.querySelector('[data-field-required]');
      if(visible){ visible.checked=field.visible!==false; visible.disabled=!!field.locked; }
      if(required){ required.checked=field.required===true; required.disabled=!!field.locked || field.visible===false; }
    });
  }

  function applyGuestFieldSettings(){
    settings.guestFields=normalizeGuestFieldSettings(settings.guestFields);
    document.querySelectorAll('[data-guest-field]').forEach(row=>{
      const key=row.dataset.guestField;
      const visible=isGuestFieldVisible(key);
      const required=isGuestFieldRequired(key);
      row.hidden=!visible;
      const control=(key==='photo'||key==='signature') ? null : row.querySelector('input:not([type="hidden"]),select,textarea');
      if(control) control.required=visible && required;
      const marker=row.querySelector('[data-required-marker]');
      if(marker) marker.hidden=!required;
    });
    document.querySelectorAll('[data-guest-section]').forEach(section=>{
      const rows=[...section.querySelectorAll('[data-guest-field]')];
      section.hidden=rows.length>0 && rows.every(row=>row.hidden);
    });
    const media=document.querySelector('.form-media-grid');
    if(media) media.hidden=!isGuestFieldVisible('photo') && !isGuestFieldVisible('signature');
    renderGuestFieldSettingsForm();
  }

  function applySettingsResponse(next){
    settings={...settings,...(next||{})};
    settings.guestFields=normalizeGuestFieldSettings(settings.guestFields);
  }

  function upsertById(list,item){
    const index=list.findIndex(x=>x.id===item.id);
    if(index>=0) list[index]=item; else list.push(item);
    return index>=0;
  }

  function debounce(fn,wait=150){
    let timer=0;
    return (...args)=>{ clearTimeout(timer); timer=setTimeout(()=>fn(...args),wait); };
  }

  function esc(v=''){
    return String(v).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[s]));
  }
  function pad(n){ return String(n).padStart(2,'0'); }
  function toLocalInput(d=new Date()){
    return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
  }
  function fmtDateTime(value){
    if(!value) return '-';
    const dateOpts=settings.dateFormat==='short'
      ? {day:'2-digit',month:'2-digit',year:'numeric'}
      : {day:'2-digit',month:'short',year:'numeric'};
    return new Intl.DateTimeFormat('id-ID',{...dateOpts,hour:'2-digit',minute:'2-digit',hour12:settings.timeFormat==='12'}).format(new Date(value));
  }
  function fmtTime(value){
    if(!value) return '-';
    return new Intl.DateTimeFormat('id-ID',{hour:'2-digit',minute:'2-digit',hour12:settings.timeFormat==='12'}).format(new Date(value));
  }
  function sameDay(value,date=new Date()){
    const a = new Date(value), b = new Date(date);
    return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate();
  }
  function sameMonth(value,date=new Date()){
    const a = new Date(value), b = new Date(date);
    return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth();
  }
  function initials(name){
    return String(name||'?').trim().split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase();
  }
  function toast(message){
    els.toast.textContent = message;
    els.toast.classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(()=>els.toast.classList.remove('show'),2600);
  }
  function notificationEventEnabled(event){
    if(event==='checkin') return notificationPrefs.checkIn!==false;
    if(event==='checkout') return notificationPrefs.checkOut!==false;
    if(event==='update') return notificationPrefs.updates!==false;
    return true;
  }

  function notificationIcon(event){
    if(event==='checkin') return '<path d="M12 5v14M5 12h14"/>';
    if(event==='checkout') return '<path d="M14 8l4 4-4 4"/><path d="M18 12H8"/><path d="M11 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6"/>';
    return '<path d="M4 20h4l11-11-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>';
  }

  function renderNotifications(){
    const list=$('notificationList');
    if(!list) return;
    const unread=notifications.filter(n=>!n.read).length;
    const badge=$('notificationBadge');
    badge.textContent=unread>99?'99+':String(unread);
    badge.classList.toggle('show',unread>0);
    $('notificationPanelSummary').textContent=unread?`${unread} belum dibaca`:(notifications.length?'Semua notifikasi sudah dibaca':'Belum ada notifikasi baru');
    list.innerHTML=notifications.length ? notifications.slice(0,100).map(n=>`
      <button class="notification-item ${esc(n.event||'update')} ${n.read?'':'unread'}" type="button" data-notification-read="${esc(n.id)}">
        <span class="notification-item-icon"><svg viewBox="0 0 24 24" aria-hidden="true">${notificationIcon(n.event)}</svg></span>
        <span><span class="notification-item-title"><span style="display:flex;gap:6px;align-items:flex-start">${n.read?'':'<i class="notification-dot"></i>'}<span>${esc(n.title)}</span></span><time>${fmtTime(n.createdAt)}</time></span><span class="notification-item-message">${esc(n.message)}</span><span class="notification-item-meta">${n.department?`<span>${esc(n.department)}</span>`:''}<span>${fmtDateTime(n.createdAt)}</span></span></span>
      </button>`).join('') : '<div class="notification-empty"><strong>Belum ada notifikasi</strong>Aktivitas tamu akan muncul di sini.</div>';
    if($('notificationMenuNote')) $('notificationMenuNote').textContent=notificationPrefs.inApp===false?'Dinonaktifkan':(unread?`${unread} belum dibaca`:'Aktif • semua terbaca');
  }

  function showBrowserNotification(item){
    if(!notificationPrefs.browser || !notificationEventEnabled(item.event)) return;
    if(!('Notification' in window) || Notification.permission!=='granted') return;
    try{
      const n=new Notification(item.title,{body:item.message,tag:`guestbook-${item.event}-${item.guestId||item.id}`,renotify:false});
      n.onclick=()=>{ try{ window.focus(); }catch{} };
    }catch{}
  }

  function receiveNotification(item){
    if(!item) return;
    seenNotificationIds.add(item.id);
    if(item.stored!==false){
      notifications=[item,...notifications.filter(n=>n.id!==item.id)].slice(0,100);
      renderNotifications();
    }
    showBrowserNotification(item);
  }

  function markNotificationRead(id){
    const item=notifications.find(n=>n.id===id);
    if(!item || item.read) return;
    item.read=true;
    renderNotifications();
    api('POST',`/api/notifications/${encodeURIComponent(id)}/read`).catch(reportApiError);
  }

  function closeOfficeInfoPanel(){
    $('officeInfoPanel')?.classList.remove('show');
    $('officeInfoBtn')?.setAttribute('aria-expanded','false');
  }

  function toggleOfficeInfoPanel(){
    const panel=$('officeInfoPanel');
    if(!panel) return;
    const show=!panel.classList.contains('show');
    closeNotificationPanel();
    panel.classList.toggle('show',show);
    $('officeInfoBtn').setAttribute('aria-expanded',String(show));
  }

  function closeNotificationPanel(){
    $('notificationPanel')?.classList.remove('show');
    $('notificationBtn')?.setAttribute('aria-expanded','false');
  }

  function toggleNotificationPanel(){
    const panel=$('notificationPanel');
    if(!panel) return;
    const show=!panel.classList.contains('show');
    closeOfficeInfoPanel();
    panel.classList.toggle('show',show);
    $('notificationBtn').setAttribute('aria-expanded',String(show));
    if(show) renderNotifications();
  }

  function updateNotificationPermissionState(){
    const status=$('notificationPermissionStatus');
    const btn=$('requestNotificationPermissionBtn');
    if(!status||!btn) return;
    if(!('Notification' in window)){
      status.textContent='Browser ini tidak mendukung notifikasi sistem.';
      btn.disabled=true;
      return;
    }
    const map={granted:'Izin diberikan. Notifikasi perangkat siap digunakan.',denied:'Izin ditolak. Ubah izin dari pengaturan browser.',default:'Izin belum diberikan.'};
    status.textContent=map[Notification.permission]||Notification.permission;
    btn.disabled=Notification.permission==='granted';
    btn.textContent=Notification.permission==='granted'?'Izin Aktif':'Minta Izin';
  }

  async function requestBrowserNotificationPermission(){
    if(!('Notification' in window)){ toast('Browser ini tidak mendukung notifikasi sistem.'); updateNotificationPermissionState(); return; }
    try{
      const permission=await Notification.requestPermission();
      updateNotificationPermissionState();
      if(permission==='granted'){
        notificationPrefs.browser=true;
        if(currentAccess().settings && isAppWritable()){
          api('PUT','/api/settings/notifications',notificationPrefs).catch(reportApiError);
        }
        renderNotificationSettings();
        toast('Izin notifikasi browser berhasil diberikan.');
      }else toast('Izin notifikasi browser belum diberikan.');
    }catch{ toast('Permintaan izin notifikasi tidak dapat diproses.'); }
  }

  function renderNotificationSettings(){
    if(!$('notificationInApp')) return;
    $('notificationInApp').checked=notificationPrefs.inApp!==false;
    $('notificationBrowser').checked=!!notificationPrefs.browser;
    $('notificationCheckIn').checked=notificationPrefs.checkIn!==false;
    $('notificationCheckOut').checked=notificationPrefs.checkOut!==false;
    $('notificationUpdates').checked=notificationPrefs.updates!==false;
    updateNotificationPermissionState();
    renderNotifications();
  }

  async function saveNotificationSettings(e){
    e.preventDefault();
    if(!guardCapability('settings','mengubah notifikasi')) return;
    const payload={
      inApp:$('notificationInApp').checked,
      browser:$('notificationBrowser').checked,
      checkIn:$('notificationCheckIn').checked,
      checkOut:$('notificationCheckOut').checked,
      updates:$('notificationUpdates').checked
    };
    try{
      const data=await busy(e.submitter,()=>api('PUT','/api/settings/notifications',payload));
      if(!data) return;
      notificationPrefs={...notificationDefaults,...data.notificationPrefs};
    }catch(error){ reportApiError(error); return; }
    renderNotifications();
    updateNotificationPermissionState();
    if(notificationPrefs.browser && 'Notification' in window && Notification.permission!=='granted') toast('Pengaturan disimpan. Tekan “Minta Izin” agar notifikasi perangkat dapat tampil.');
    else toast('Pengaturan notifikasi berhasil disimpan.');
  }

  function currentActor(){
    if(actor.type==='team') return {...actor,type:'team'};
    return {type:'owner',id:actor.id,name:profile.name||actor.name||'Administrator',role:'Owner',permissions:{guests:true,reports:true,settings:true}};
  }

  function currentAccess(){ return CATAMU_FLOW.roleAccess(currentActor()); }
  function subscriptionState(){ return CATAMU_FLOW.subscriptionState(subscription); }
  function isAppWritable(){ return CATAMU_FLOW.subscriptionWritable(subscription); }
  function isSubscriptionActive(){ return ['active','active-pending'].includes(subscriptionState()); }

  function guardCapability(capability,label='melakukan tindakan ini',requireWritable=true){
    const access=currentAccess();
    if(!access[capability]){ toast(`Akses ditolak. Role Anda tidak diizinkan untuk ${label}.`); return false; }
    if(requireWritable && !isAppWritable()){
      const state=subscriptionState();
      toast(state==='pending'?'Pembayaran masih menunggu verifikasi. Aplikasi sementara dalam mode baca saja.':'Masa trial/langganan telah berakhir. Aplikasi dalam mode baca saja.');
      return false;
    }
    return true;
  }

  function canOpenSettingsView(view){
    const access=currentAccess();
    if(view==='subscription') return access.subscriptionManage;
    if(view==='account') return access.accountManage;
    if(view==='team') return access.teamManage;
    if(['departments','guest-fields','application','notifications'].includes(view)) return access.settings;
    return true;
  }

  let lastAccessSignature='';
  function applyAccessControl(force=false){
    const actor=currentActor();
    const access=currentAccess();
    const state=subscriptionState();
    const writable=isAppWritable();
    const signature=[actor.type,actor.id||'',actor.role,state,writable,access.guestsView,access.guestsWrite,access.reports,access.settings,access.teamManage].join('|');
    if(!force && signature===lastAccessSignature) return;
    lastAccessSignature=signature;


    const setHidden=(selector,hidden)=>document.querySelectorAll(selector).forEach(el=>{el.classList.toggle('access-hidden',!!hidden);});
    setHidden('[data-page="form"],[data-route="form"],[data-go="form"]',!(access.guestsWrite&&writable));
    setHidden('[data-page="guests"],[data-route="guests"]',!(access.guestsView||access.reports));
    setHidden('#exportBtn,#printBtn,#shareGuest58Btn,#shareGuestA4Btn',!access.reports);
    setHidden('#page-dashboard .grid-stats,#page-dashboard .dashboard-layout',!(access.guestsView||access.reports));
    setHidden('[data-setting-action="subscription"]',!access.subscriptionManage);
    setHidden('[data-setting-action="account"]',!access.accountManage);
    setHidden('[data-setting-action="team"]',!access.teamManage);
    ['departments','guest-fields','application','notifications'].forEach(view=>setHidden(`[data-setting-action="${view}"]`,!access.settings));
    setHidden('.requires-guest-write',!(access.guestsWrite&&writable));
    setHidden('.requires-team-manage',!(access.teamManage&&writable));
    setHidden('.requires-settings-write',!(access.settings&&writable));

    const guestSubmit=$('guestForm')?.querySelector('button[type="submit"]'); if(guestSubmit) guestSubmit.disabled=!(access.guestsWrite&&writable);
    const addTeam=$('addTeamBtn'); if(addTeam) addTeam.disabled=!(access.teamManage&&writable);
    const depSave=$('departmentSaveBtn'); if(depSave) depSave.disabled=!(access.settings&&writable);
    const clearData=$('clearDataBtn'); if(clearData) clearData.disabled=!(access.settings&&writable);
  }

  const pageMeta = {
    dashboard:['Dashboard','Ringkasan kunjungan kantor hari ini'],
    guests:['Daftar Tamu',''],
    form:['Registrasi Tamu',''],
    settings:['Pengaturan','Identitas kantor dan preferensi aplikasi']
  };

  const routeHashes = {
    dashboard:'dashboard',
    guests:'tamu',
    form:'registrasi',
    settings:'pengaturan'
  };
  const hashRoutes = Object.fromEntries(Object.entries(routeHashes).map(([route,hash])=>[hash,route]));

  function renderRoute(page){
    if(!pageMeta[page]) page='dashboard';
    const access=currentAccess();
    if(page==='form' && !(access.guestsWrite&&isAppWritable())){ toast('Registrasi tidak tersedia untuk role atau status langganan saat ini.'); page=(access.guestsView||access.reports)?'guests':'dashboard'; }
    if(page==='guests' && !(access.guestsView||access.reports)) page='dashboard';
    document.querySelectorAll('.page').forEach(x=>x.classList.remove('active','entering'));
    document.querySelectorAll('.nav-btn').forEach(x=>x.classList.toggle('active',x.dataset.page===page));
    document.querySelectorAll('.bottom-nav-btn').forEach(x=>x.classList.toggle('active',x.dataset.route===page));
    const target=$('page-'+page);
    target.classList.add('active');
    void target.offsetWidth;
    target.classList.add('entering');
    setTimeout(()=>target.classList.remove('entering'),260);
    els.headerTitle.textContent=pageMeta[page][0];
    els.headerSubtitle.textContent=pageMeta[page][1];
    document.title=`${pageMeta[page][0]} • CATAMU`;
    closeSidebar();
    if(page==='guests') renderGuests();
    if(page==='dashboard') renderDashboard();
    if(page==='form'){ renderDepartmentChoices(); updateFormHeader(); setTimeout(()=>resizeSignatureCanvas(true),30); }
    else stopCamera();
    if(page==='settings') openSettingsView('menu');
    applyAccessControl(true);
    window.scrollTo({top:0,behavior:'smooth'});
  }

  function go(page, replace=false){
    if(!pageMeta[page]) page='dashboard';
    const nextHash='#'+routeHashes[page];
    if(location.hash!==nextHash){
      if(replace) history.replaceState(null,'',nextHash);
      else location.hash=routeHashes[page];
    }else{
      renderRoute(page);
    }
  }

  function routeFromHash(){
    const raw=location.hash.replace('#','').trim().toLowerCase();
    const page=hashRoutes[raw] || (pageMeta[raw] ? raw : 'dashboard');
    if(!raw){
      history.replaceState(null,'','#'+routeHashes[page]);
    }
    renderRoute(page);
  }

  function openSidebar(){ els.sidebar.classList.add('open'); els.overlay.classList.add('show'); }
  function closeSidebar(){ els.sidebar.classList.remove('open'); els.overlay.classList.remove('show'); }

  const settingViews = {
    subscription:'settingsSubscriptionView',
    account:'settingsAccountView',
    team:'settingsTeamView',
    departments:'settingsDepartmentsView',
    'guest-fields':'settingsGuestFieldsView',
    application:'settingsApplicationView',
    notifications:'settingsNotificationsView',
    'download-desktop':'settingsDesktopView',
    contact:'settingsContactView',
    feedback:'settingsFeedbackView',
    rating:'settingsRatingView',
    license:'settingsLicenseView'
  };

  const settingViewMeta = {
    subscription:['Berlangganan','Status paket dan masa aktif aplikasi.'],
    account:['Akun','Profil pengguna dan penguncian aplikasi.'],
    team:['Kelola Tim','Anggota, role, status, dan hak akses.'],
    departments:['Departemen','Kelola departemen tujuan kunjungan tamu.'],
    'guest-fields':['Atur Data Tamu','Atur field Registrasi Tamu.'],
    application:['Pengaturan Aplikasi','Identitas kantor dan preferensi tampilan.'],
    notifications:['Notifikasi','Atur notifikasi aplikasi dan perangkat.'],
    'download-desktop':['Instal di HP','Pasang CATAMU di perangkat.'],
    contact:['Kontak Kami','Hubungi kontak kantor yang tersimpan pada aplikasi.'],
    feedback:['Masukan','Simpan saran, laporan masalah, atau ide pengembangan.'],
    rating:['Rating Aplikasi','Nilai pengalaman penggunaan aplikasi.'],
    license:['About','Kebijakan Privasi dan Syarat & Ketentuan.']
  };

  function openSettingsView(view='menu'){
    const menu=$('settingsMenuView');
    if(view!=='menu' && !canOpenSettingsView(view)){ toast('Akses pengaturan ini tidak diizinkan untuk role Anda.'); view='menu'; }
    if(!menu) return;
    document.querySelectorAll('.settings-subview').forEach(v=>v.classList.remove('active'));
    document.querySelectorAll('.settings-item').forEach(v=>v.classList.remove('active'));
    if(view==='menu'){
      menu.style.display='';
      els.headerTitle.textContent='Pengaturan';
      els.headerSubtitle.textContent='';
      document.title='Pengaturan • CATAMU';
      renderSettingsMenuNotes();
      applyAccessControl(true);
      return;
    }
    const targetId=settingViews[view];
    const target=targetId ? $(targetId) : null;
    if(!target){ openSettingsView('menu'); return; }
    menu.style.display='none';
    target.classList.add('active');
    const selected=document.querySelector(`[data-setting-action="${view}"]`);
    if(selected) selected.classList.add('active');
    const meta=settingViewMeta[view] || pageMeta.settings;
    els.headerTitle.textContent=meta[0];
    els.headerSubtitle.textContent=meta[1];
    document.title=`${meta[0]} • CATAMU`;

    if(view==='subscription') renderSubscription();
    if(view==='account') renderProfile();
    if(view==='team') renderTeam();
    if(view==='departments') renderDepartments();
    if(view==='guest-fields') renderGuestFieldSettingsForm();
    if(view==='application') applySettings();
    if(view==='download-desktop') renderPwaInstallState();
    if(view==='notifications') renderNotificationSettings();
    if(view==='contact') renderContact();
    if(view==='feedback') renderFeedbacks();
    if(view==='rating') renderRating();
    applyAccessControl(true);
    window.scrollTo({top:0,behavior:'smooth'});
  }

  function handleSettingAction(action){
    if(action==='logout'){
      if(confirm('Kunci sesi aktif? Data tidak akan dihapus.')) lockApp();
      return;
    }
    openSettingsView(action);
  }

  function renderSettingsMenuNotes(){
    const state=subscriptionState();
    const labels={trial:`Trial • sampai ${formatDateOnly(subscription.trialExpiresAt)}`,'trial-pending':'Trial • pembayaran pending',active:subscription.lifetime?'Lifetime • tanpa batas waktu':`${subscription.plan} • aktif sampai ${formatDateOnly(subscription.expiresAt)}`,'active-pending':`${subscription.plan} • perpanjangan pending`,pending:'Menunggu verifikasi • baca saja',expired:'Masa aktif berakhir • baca saja'};
    $('subscriptionMenuNote').textContent=labels[state]||'Status tidak diketahui';
    $('accountMenuNote').textContent=profile.name ? `${profile.name}${profile.hasPin?' • PIN aktif':''}` : 'Profil pengguna & PIN';
    $('teamMenuNote').textContent=`${team.length} anggota`;
    const activeDepartments=departments.filter(d=>d.active!==false).length;
    $('departmentMenuNote').textContent=`${departments.length} departemen • ${activeDepartments} aktif`;
    const unreadNotifications=notifications.filter(n=>!n.read).length;
    $('notificationMenuNote').textContent=notificationPrefs.inApp===false?'Dinonaktifkan':(unreadNotifications?`${unreadNotifications} belum dibaca`:'Aktif • semua terbaca');
    $('feedbackMenuNote').textContent=feedbacks.length ? `${feedbacks.length} masukan tersimpan` : 'Belum ada masukan';
    $('ratingMenuNote').textContent=rating.score ? `${rating.score}/5 bintang` : 'Belum memberi rating';
  }

  function formatDateOnly(value){
    if(!value) return 'Tanpa batas';
    return new Intl.DateTimeFormat('id-ID',settings.dateFormat==='short'
      ? {day:'2-digit',month:'2-digit',year:'numeric'}
      : {day:'2-digit',month:'long',year:'numeric'}).format(new Date(value));
  }

  function formatCurrency(value){ return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(Number(value)||0); }

  function renderSubscription(){
    const state=subscriptionState();
    const stateLabel={trial:'Trial Aktif','trial-pending':'Trial + Pending',active:'Aktif','active-pending':'Aktif + Pending',pending:'Menunggu Verifikasi',expired:'Kedaluwarsa'}[state]||state;
    $('subscriptionStatus').textContent=stateLabel;
    $('subscriptionPlan').textContent=(state==='trial'||state==='trial-pending')?`Trial ${subscription.trialDays||3} Hari`:(subscription.plan||'1 Tahun');
    $('subscriptionExpiry').textContent=(state==='active'||state==='active-pending')?formatDateOnly(subscription.expiresAt):formatDateOnly(subscription.trialExpiresAt);
    const notice=$('subscriptionNotice'), noticeText=$('subscriptionNoticeText');
    notice.className='subscription-notice';
    if(state==='trial'){ noticeText.textContent=`Akses penuh trial tersedia sampai ${formatDateOnly(subscription.trialExpiresAt)}.`; }
    else if(state==='trial-pending'){ notice.classList.add('pending'); noticeText.textContent=`Trial tetap aktif sampai ${formatDateOnly(subscription.trialExpiresAt)}. Pembayaran menunggu verifikasi dan belum menambah masa aktif.`; }
    else if(state==='active'){ noticeText.textContent=subscription.lifetime?'Paket Lifetime aktif tanpa batas waktu.':`Paket ${subscription.plan} aktif sampai ${formatDateOnly(subscription.expiresAt)}.`; }
    else if(state==='active-pending'){ notice.classList.add('pending'); noticeText.textContent=`Paket aktif sampai ${formatDateOnly(subscription.expiresAt)}. Perpanjangan menunggu verifikasi.`; }
    else if(state==='pending'){ notice.classList.add('readonly'); noticeText.textContent='Pembayaran menunggu verifikasi. Karena trial/langganan sudah berakhir, aplikasi berada dalam mode baca saja.'; }
    else { notice.classList.add('readonly'); noticeText.textContent='Trial/langganan telah berakhir. Data tetap dapat dilihat, tetapi perubahan diblokir sampai paket aktif.'; }
    const rejected=subscription.lastRejectedPayment;
    if(rejected && !subscription.pendingPayment){
      noticeText.textContent+=` Pembayaran ${rejected.planName||'paket'} terakhir ditolak admin${rejected.note?`: ${rejected.note}`:''}. Silakan kirim ulang bukti pembayaran.`;
    }
    document.querySelectorAll('[data-plan-days]').forEach(btn=>{
      const pending=!!subscription.pendingPayment;
      btn.disabled=pending||!!subscription.lifetime;
      btn.textContent=subscription.lifetime?'Paket Lifetime Aktif':pending?'Menunggu Verifikasi':`${isSubscriptionActive()?'Perpanjang':'Aktifkan'} ${btn.dataset.planName}`;
    });
    renderSettingsMenuNotes();
    applyAccessControl(true);
  }

  function resetQrisPaymentProof(){
    qrisPaymentProofData='';
    const input=$('qrisPaymentProofInput');
    const preview=$('qrisPaymentProofPreview');
    const empty=$('qrisPaymentProofEmpty');
    const removeBtn=$('removeQrisPaymentProofBtn');
    const confirmBtn=$('confirmQrisRenewalBtn');
    if(input) input.value='';
    if(preview){ preview.removeAttribute('src'); preview.hidden=true; }
    if(empty) empty.hidden=false;
    if(removeBtn) removeBtn.hidden=true;
    if(confirmBtn) confirmBtn.disabled=true;
  }

  function setQrisPaymentProof(dataUrl=''){
    qrisPaymentProofData=dataUrl||'';
    const hasProof=!!qrisPaymentProofData;
    const preview=$('qrisPaymentProofPreview');
    const empty=$('qrisPaymentProofEmpty');
    const removeBtn=$('removeQrisPaymentProofBtn');
    const confirmBtn=$('confirmQrisRenewalBtn');
    if(preview){
      if(hasProof) preview.src=qrisPaymentProofData; else preview.removeAttribute('src');
      preview.hidden=!hasProof;
    }
    if(empty) empty.hidden=hasProof;
    if(removeBtn) removeBtn.hidden=!hasProof;
    if(confirmBtn) confirmBtn.disabled=!hasProof;
  }

  function loadQrisPaymentProof(file){
    if(!file) return;
    if(!file.type.startsWith('image/')){ toast('Bukti pembayaran harus berupa file gambar.'); return; }
    if(file.size>5*1024*1024){ toast('Ukuran bukti pembayaran maksimal 5 MB.'); return; }
    const reader=new FileReader();
    reader.onload=async e=>{
      const data=await compressImageData(e.target.result,900,.72);
      setQrisPaymentProof(data);
      toast('Bukti pembayaran siap digunakan.');
    };
    reader.readAsDataURL(file);
  }

  function openQrisRenewalModal(days,planName){
    if(!guardCapability('subscriptionManage','mengelola langganan',false)) return;
    if(subscription.pendingPayment){ toast('Masih ada pembayaran yang menunggu verifikasi.'); return; }
    pendingRenewal={days:Number(days)||365,planName:planName||'1 Tahun',amount:99000};
    $('qrisPlanName').textContent=pendingRenewal.planName;
    $('qrisPlanTotal').textContent=formatCurrency(pendingRenewal.amount);
    resetQrisPaymentProof();
    $('qrisRenewalModal').classList.add('show');
  }

  async function confirmQrisRenewal(){
    if(!guardCapability('subscriptionManage','mengirim pembayaran langganan',false)) return;
    if(!pendingRenewal) return;
    if(!qrisPaymentProofData){ toast('Tambahkan bukti foto pembayaran terlebih dahulu.'); return; }
    try{
      const data=await busy($('confirmQrisRenewalBtn'),()=>api('POST','/api/payments',{
        days:pendingRenewal.days,
        planName:pendingRenewal.planName,
        proof:qrisPaymentProofData
      }));
      if(!data) return;
      subscription=CATAMU_FLOW.normalizeSubscription({...subscriptionDefaults,...data.subscription});
    }catch(error){ reportApiError(error); return; }
    pendingRenewal=null;
    resetQrisPaymentProof();
    $('qrisRenewalModal').classList.remove('show');
    renderSubscription();
    renderDashboard();
    renderGuests();
    toast('Bukti pembayaran dikirim. Status sekarang Menunggu Verifikasi.');
  }

  function renderProfileAvatar(){
    const avatar=$('profileAvatar');
    if(!avatar) return;
    avatar.innerHTML=profile.photo
      ? `<img src="${esc(profile.photo)}" alt="Foto profil" />`
      : esc(initials(profile.name||'Administrator'));
  }

  function renderProfile(){
    $('profileName').value=profile.name||'';
    $('profileEmail').value=profile.email||'';
    $('profilePhone').value=profile.phone||'';
    $('profilePin').value='';
    $('profilePinConfirm').value='';
    $('profileDisplayName').textContent=profile.name||'Administrator';
    $('profileDisplayRole').textContent='Owner / Administrator';
    renderProfileAvatar();
  }

  async function saveAccount(e){
    e.preventDefault();
    if(!guardCapability('accountManage','mengubah akun Owner',false)) return;
    const pin=$('profilePin').value.trim();
    const confirmPin=$('profilePinConfirm').value.trim();
    if(pin && !/^\d{4,6}$/.test(pin)){ toast('PIN harus 4–6 digit angka.'); return; }
    if(pin && pin!==confirmPin){ toast('Konfirmasi PIN tidak sama.'); return; }
    try{
      const data=await busy(e.submitter,()=>api('PUT','/api/account',{
        name:$('profileName').value.trim()||'Administrator',
        email:$('profileEmail').value.trim(),
        phone:$('profilePhone').value.trim(),
        pin,
        pin_confirmation:confirmPin
      }));
      if(!data) return;
      profile={...profile,...data.profile};
    }catch(error){ reportApiError(error); return; }
    renderProfile();
    renderSettingsMenuNotes();
    applyAccessControl(true);
    toast('Profil akun berhasil disimpan.');
  }

  function openDeleteAccountModal(){
    if(!guardCapability('accountManage','menghapus akun Owner',false)) return;
    const input=$('deleteAccountConfirmText');
    input.value='';
    $('confirmDeleteAccountBtn').disabled=true;
    $('deleteAccountModal').classList.add('show');
    setTimeout(()=>input.focus(),40);
  }

  function updateDeleteAccountConfirmation(){
    $('confirmDeleteAccountBtn').disabled=$('deleteAccountConfirmText').value.trim()!=='HAPUS AKUN';
  }

  async function deleteAccountAndData(){
    if(!guardCapability('accountManage','menghapus akun Owner',false)) return;
    const confirmation=$('deleteAccountConfirmText').value.trim();
    if(confirmation!=='HAPUS AKUN') return;
    try{
      const data=await busy($('confirmDeleteAccountBtn'),()=>api('DELETE','/api/account',{confirmation}));
      if(!data) return;
      location.href=data.redirect;
    }catch(error){ reportApiError(error); }
  }

  async function loadProfilePhoto(file){
    if(!guardCapability('accountManage','mengubah foto profil Owner',false)) return;
    if(!file) return;
    if(file.size>1024*1024){ toast('Ukuran foto maksimal 1 MB.'); return; }
    try{
      const photo=await readFileAsDataUrl(file);
      const data=await api('POST','/api/account/photo',{photo});
      profile={...profile,...data.profile};
    }catch(error){ reportApiError(error); $('profilePhoto').value=''; return; }
    renderProfileAvatar();
    toast('Foto profil berhasil diperbarui.');
  }

  function normalizeTeamEmail(value){ return String(value||'').trim().toLowerCase(); }
  function normalizeTeamPhone(value){ return String(value||'').replace(/\D/g,''); }

  function resetTeamForm(){
    $('teamForm').reset();
    $('teamEditId').value='';
    $('teamRole').value='Resepsionis';
    $('permGuests').checked=true;
    $('permReports').checked=false;
    $('permSettings').checked=false;
    $('teamActive').checked=true;
    $('teamPassword').value='';
    $('teamPasswordConfirm').value='';
    $('teamPassword').type='password';
    $('teamPasswordConfirm').type='password';
    document.querySelectorAll('#teamModal [data-password-toggle]').forEach(btn=>btn.textContent='Tampilkan');
    $('teamPassword').required=true;
    $('teamPasswordConfirm').required=true;
    $('teamPasswordHelp').textContent='Minimal 6 karakter.';
    $('teamModalTitle').textContent='Tambah Anggota';
    $('teamModalSubtitle').textContent='Lengkapi data, password, dan hak akses anggota tim.';
    $('teamSaveBtn').textContent='Simpan Anggota';
    applyTeamRoleDefaults('Resepsionis',true);
  }

  function applyTeamRoleDefaults(role=$('teamRole').value,force=false){
    if(role==='Admin'){
      if(force){ $('permGuests').checked=true; $('permReports').checked=true; $('permSettings').checked=true; }
      $('permSettings').disabled=false;
    }else if(role==='Resepsionis'){
      if(force){ $('permGuests').checked=true; $('permReports').checked=false; $('permSettings').checked=false; }
      $('permSettings').disabled=false;
    }else{
      if(force){ $('permGuests').checked=true; $('permReports').checked=true; }
      $('permSettings').checked=false; $('permSettings').disabled=true;
    }
  }

  function openTeamModal(mode='add'){
    if(mode==='add') resetTeamForm();
    $('teamModal').classList.add('show');
    setTimeout(()=>$('teamName').focus(),0);
  }

  async function saveTeamMember(e){
    e.preventDefault();
    if(!guardCapability('teamManage','mengelola anggota tim')) return;
    const id=$('teamEditId').value;
    const existing=id?team.find(x=>x.id===id):null;
    const password=$('teamPassword').value;
    const passwordConfirm=$('teamPasswordConfirm').value;
    $('teamPassword').required=!id;
    $('teamPasswordConfirm').required=!id;
    if(!id && !password){ toast('Password anggota wajib diisi.'); $('teamPassword').focus(); return; }
    if(password && password.length<6){ toast('Password minimal 6 karakter.'); $('teamPassword').focus(); return; }
    if(password!==passwordConfirm){ toast('Konfirmasi password tidak sama.'); $('teamPasswordConfirm').focus(); return; }
    const member={
      name:$('teamName').value.trim(),
      email:$('teamEmail').value.trim(),
      phone:$('teamPhone').value.trim(),
      role:$('teamRole').value,
      active:$('teamActive').checked,
      permissions:{
        guests:$('permGuests').checked,
        reports:$('permReports').checked,
        settings:$('teamRole').value==='Viewer'?false:$('permSettings').checked
      },
      password,
      password_confirmation:passwordConfirm
    };
    if(!member.name){ toast('Nama anggota wajib diisi.'); return; }
    const emailKey=normalizeTeamEmail(member.email);
    const phoneKey=normalizeTeamPhone(member.phone);
    if(emailKey && team.some(x=>x.id!==existing?.id && normalizeTeamEmail(x.email)===emailKey)){ toast('Email sudah digunakan anggota lain.'); return; }
    if(phoneKey && team.some(x=>x.id!==existing?.id && normalizeTeamPhone(x.phone)===phoneKey)){ toast('Nomor HP sudah digunakan anggota lain.'); return; }
    let updated=false;
    try{
      const data=await busy($('teamSaveBtn'),()=>api(id?'PUT':'POST',id?`/api/team/${encodeURIComponent(id)}`:'/api/team',member));
      if(!data) return;
      updated=upsertById(team,data.member);
    }catch(error){ reportApiError(error); return; }
    resetTeamForm();
    closeModal('teamModal');
    renderTeam();
    toast(updated?'Data anggota diperbarui.':'Anggota berhasil ditambahkan.');
  }

  function editTeamMember(id){
    if(!guardCapability('teamManage','mengedit anggota tim')) return;
    const m=team.find(x=>x.id===id); if(!m) return;
    $('teamEditId').value=m.id;
    $('teamName').value=m.name||'';
    $('teamEmail').value=m.email||'';
    $('teamPhone').value=m.phone||'';
    $('teamRole').value=m.role||'Resepsionis';
    $('teamActive').checked=m.active!==false;
    $('permGuests').checked=!!m.permissions?.guests;
    $('permReports').checked=!!m.permissions?.reports;
    $('permSettings').checked=!!m.permissions?.settings;
    $('teamPassword').value='';
    $('teamPasswordConfirm').value='';
    $('teamPassword').type='password';
    $('teamPasswordConfirm').type='password';
    document.querySelectorAll('#teamModal [data-password-toggle]').forEach(btn=>btn.textContent='Tampilkan');
    $('teamPassword').required=false;
    $('teamPasswordConfirm').required=false;
    $('teamPasswordHelp').textContent=m.hasPassword?'Kosongkan jika tidak ingin mengganti password.':'Belum ada password. Isi minimal 6 karakter untuk membuat password.';
    $('teamModalTitle').textContent='Edit Anggota';
    $('teamModalSubtitle').textContent='Perbarui data, password, role, status, dan hak akses anggota.';
    $('teamSaveBtn').textContent='Simpan Perubahan';
    applyTeamRoleDefaults(m.role||'Resepsionis',false);
    openTeamModal('edit');
  }

  async function deleteTeamMember(id){
    if(!guardCapability('teamManage','menghapus anggota tim')) return;
    const m=team.find(x=>x.id===id); if(!m) return;
    if(!confirm(`Hapus anggota "${m.name}"?`)) return;
    try{ await api('DELETE',`/api/team/${encodeURIComponent(id)}`); }
    catch(error){ reportApiError(error); return; }
    team=team.filter(x=>x.id!==id);
    renderTeam();
    toast('Anggota telah dihapus.');
  }

  function renderTeam(){
    const active=team.filter(m=>m.active!==false).length;
    $('teamCountText').textContent=team.length?`${team.length} anggota • ${active} aktif`:'Belum ada anggota';
    $('teamMenuNote').textContent=`${team.length} anggota`;

    const query=$('teamSearchInput').value.trim().toLowerCase();
    const role=$('teamRoleFilter').value;
    const status=$('teamStatusFilter').value;
    const filtered=[...team].filter(m=>{
      const haystack=[m.name,m.email,m.phone,m.role].join(' ').toLowerCase();
      if(query && !haystack.includes(query)) return false;
      if(role && m.role!==role) return false;
      if(status==='active' && m.active===false) return false;
      if(status==='inactive' && m.active!==false) return false;
      return true;
    }).sort((a,b)=>String(a.name||'').localeCompare(String(b.name||''),'id'));

    $('teamResultCount').textContent=`${filtered.length} data`;
    $('teamList').innerHTML=filtered.length ? filtered.map(m=>{
      const permissions=[
        ['Data Tamu',!!m.permissions?.guests],
        ['Laporan',!!m.permissions?.reports],
        ['Pengaturan',!!m.permissions?.settings]
      ];
      const contact=[m.email,m.phone].filter(Boolean).map(esc).join(' • ')||'Kontak belum diatur';
      return `<div class="team-list-row">
        <div class="team-list-col member-col"><div class="avatar">${esc(initials(m.name))}</div><div class="team-list-member-copy"><span class="team-member-name">${esc(m.name)}</span><div class="team-member-contact">${contact}</div></div></div>
        <div class="team-list-col role-col"><span class="team-member-role">${esc(m.role||'Resepsionis')}</span></div>
        <div class="team-list-col status-col"><span class="status-pill ${m.active!==false?'active':'inactive'}">${m.active!==false?'Aktif':'Nonaktif'}</span></div>
        <div class="team-list-col password-col"><span class="team-password-status ${m.hasPassword?'active':''}">${m.hasPassword?'Password Aktif':'Belum Diatur'}</span></div>
        <div class="team-list-col access-col"><div class="team-permissions">${permissions.map(([label,on])=>`<span class="team-permission-chip ${on?'enabled':''}">${on?'✓ ':''}${label}</span>`).join('')}</div></div>
        <div class="team-list-col action-col"><button class="action-btn requires-team-manage" type="button" data-team-edit="${m.id}" title="Edit">${uiIcon('edit')}<span class="team-action-label">Edit</span></button><button class="action-btn danger-action requires-team-manage" type="button" data-team-delete="${m.id}" title="Hapus">${uiIcon('trash')}<span class="team-action-label">Hapus</span></button></div>
      </div>`;
    }).join('') : `<div class="empty" style="padding:24px 10px">${team.length?'Tidak ada anggota yang cocok dengan filter.':'Belum ada anggota tim.'}</div>`;
    renderSettingsMenuNotes();
    applyAccessControl(true);
  }

  function resetDepartmentForm(){
    $('departmentForm').reset();
    $('departmentEditId').value='';
    $('departmentActive').checked=true;
    $('departmentFormTitle').textContent='Tambah Departemen';
    $('departmentFormState').textContent='Tambah baru';
    $('departmentSaveBtn').textContent='Simpan Departemen';
    $('departmentFormCard').classList.remove('editing');
  }

  async function saveDepartment(e){
    e.preventDefault();
    if(!guardCapability('settings','mengubah departemen')) return;
    const id=$('departmentEditId').value;
    const name=$('departmentName').value.trim();
    const code=$('departmentCode').value.trim().toUpperCase();
    const description=$('departmentDescription').value.trim();
    if(!name){ toast('Nama departemen wajib diisi.'); return; }
    const duplicate=departments.find(d=>d.id!==id && d.name.trim().toLowerCase()===name.toLowerCase());
    if(duplicate){ toast('Nama departemen sudah digunakan.'); return; }
    if(code && departments.some(d=>d.id!==id && String(d.code||'').toLowerCase()===code.toLowerCase())){
      toast('Kode departemen sudah digunakan.'); return;
    }
    const item={name,code,description,active:$('departmentActive').checked};
    let updated=false;
    try{
      const data=await busy($('departmentSaveBtn'),()=>api(id?'PUT':'POST',id?`/api/departments/${encodeURIComponent(id)}`:'/api/departments',item));
      if(!data) return;
      updated=upsertById(departments,data.department);
    }catch(error){ reportApiError(error); return; }
    resetDepartmentForm();
    renderDepartments();
    renderDepartmentChoices();
    toast(updated?'Departemen berhasil diperbarui.':'Departemen berhasil ditambahkan.');
  }

  function editDepartment(id){
    if(!guardCapability('settings','mengedit departemen')) return;
    const d=departments.find(x=>x.id===id); if(!d) return;
    $('departmentEditId').value=d.id;
    $('departmentName').value=d.name||'';
    $('departmentCode').value=d.code||'';
    $('departmentDescription').value=d.description||'';
    $('departmentActive').checked=d.active!==false;
    $('departmentFormTitle').textContent='Edit Departemen';
    $('departmentFormState').textContent='Mengedit';
    $('departmentSaveBtn').textContent='Simpan Perubahan';
    $('departmentFormCard').classList.add('editing');
    $('departmentFormCard').scrollIntoView({behavior:'smooth',block:'start'});
    setTimeout(()=>$('departmentName').focus(),220);
  }

  async function deleteDepartment(id){
    if(!guardCapability('settings','menghapus departemen')) return;
    const d=departments.find(x=>x.id===id); if(!d) return;
    const used=guests.filter(g=>g.departmentId===id || String(g.meet||'').toLowerCase()===String(d.name||'').toLowerCase()).length;
    const suffix=used?` ${used} data tamu lama tetap menyimpan nama departemen ini.`:'';
    if(!confirm(`Hapus departemen "${d.name}"?${suffix}`)) return;
    try{ await api('DELETE',`/api/departments/${encodeURIComponent(id)}`); }
    catch(error){ reportApiError(error); return; }
    departments=departments.filter(x=>x.id!==id);
    guests.forEach(g=>{ if(g.departmentId===id) g.departmentId=''; });
    renderDepartments();
    renderDepartmentChoices();
    toast('Departemen telah dihapus. Data tamu lama tetap tersimpan.');
  }

  function renderDepartments(){
    const active=departments.filter(d=>d.active!==false).length;
    const inactive=Math.max(0,departments.length-active);
    $('departmentTotalStat').textContent=departments.length;
    $('departmentActiveStat').textContent=active;
    $('departmentInactiveStat').textContent=inactive;
    $('departmentCountText').textContent=departments.length?`${departments.length} departemen • ${active} aktif • ${inactive} nonaktif`:'Belum ada departemen.';
    const query=String($('departmentSearchInput')?.value||'').trim().toLowerCase();
    const status=$('departmentStatusFilter')?.value||'';
    const filtered=departments.filter(d=>{
      const isActive=d.active!==false;
      const statusMatch=!status || (status==='active'&&isActive) || (status==='inactive'&&!isActive);
      const haystack=`${d.name||''} ${d.code||''} ${d.description||''}`.toLowerCase();
      return statusMatch && (!query || haystack.includes(query));
    }).sort((a,b)=>String(a.name).localeCompare(String(b.name),'id'));
    if($('departmentResultCount')) $('departmentResultCount').textContent=`${filtered.length} dari ${departments.length} data`;
    $('departmentList').innerHTML=filtered.length ? filtered.map(d=>`
      <div class="department-row">
        <div class="department-code-badge">${esc((d.code||initials(d.name)).slice(0,3).toUpperCase())}</div>
        <div class="department-main">
          <div class="department-title-row"><span class="department-name">${esc(d.name)}</span><span class="status-pill ${d.active!==false?'active':'inactive'}">${d.active!==false?'Aktif':'Nonaktif'}</span></div>
          <div class="department-meta">${esc(d.code||'Tanpa kode')}</div>
          ${d.description?`<div class="department-description">${esc(d.description)}</div>`:''}
        </div>
        <div class="department-actions">
          <button class="action-btn requires-settings-write" type="button" data-department-edit="${d.id}" title="Edit departemen" aria-label="Edit ${esc(d.name)}">${uiIcon('edit')}<span class="department-action-label">Edit</span></button>
          <button class="action-btn danger-action requires-settings-write" type="button" data-department-delete="${d.id}" title="Hapus departemen" aria-label="Hapus ${esc(d.name)}">${uiIcon('trash')}<span class="department-action-label">Hapus</span></button>
        </div>
      </div>`).join('') : `<div class="department-empty">${departments.length?'Tidak ada departemen yang sesuai pencarian atau filter.':'Belum ada departemen. Tambahkan departemen pertama melalui form di samping.'}</div>`;
    renderSettingsMenuNotes();
    applyAccessControl(true);
  }

  function renderDepartmentChoices(preferred=''){
    const select=$('meetDepartment');
    if(!select) return;
    const current=preferred || select.value;
    const active=[...departments].filter(d=>d.active!==false).sort((a,b)=>String(a.name).localeCompare(String(b.name),'id'));
    select.innerHTML='<option value="">Pilih departemen</option>'+
      active.map(d=>`<option value="${esc(d.id)}">${esc(d.name)}${d.code?` (${esc(d.code)})`:''}</option>`).join('')+
      '<option value="__manual__">Lainnya / Tulis Manual</option>';
    if(current==='__manual__' || active.some(d=>d.id===current)) select.value=current;
    else select.value='';
    toggleMeetManual();
  }

  function toggleMeetManual(){
    const manual=$('meetManual');
    if(!manual) return;
    const isManual=$('meetDepartment').value==='__manual__';
    manual.style.display=isManual?'':'none';
    manual.required=isManual;
    if(isManual) setTimeout(()=>manual.focus(),0);
  }

  function resolveMeetValue(){
    const selected=$('meetDepartment').value;
    if(selected==='__manual__') return $('meetManual').value.trim();
    const department=departments.find(d=>d.id===selected && d.active!==false);
    return department?.name||'';
  }

  function resolveMeetDepartmentId(){
    const selected=$('meetDepartment').value;
    return departments.some(d=>d.id===selected && d.active!==false) ? selected : '';
  }

  function setMeetValue(meet='',departmentId=''){
    renderDepartmentChoices();
    const active=departments.filter(d=>d.active!==false);
    let department=departmentId ? active.find(d=>d.id===departmentId) : null;
    if(!department && meet) department=active.find(d=>String(d.name).toLowerCase()===String(meet).toLowerCase());
    if(department){
      $('meetDepartment').value=department.id;
      $('meetManual').value='';
    }else if(meet){
      $('meetDepartment').value='__manual__';
      $('meetManual').value=meet;
    }else{
      $('meetDepartment').value='';
      $('meetManual').value='';
    }
    toggleMeetManual();
  }

  function renderContact(){
    const phone=settings.phone||'';
    const email=settings.email||'';
    $('contactWhatsAppText').textContent=phone||'Belum diatur';
    $('contactPhoneText').textContent=phone||'Belum diatur';
    $('contactEmailText').textContent=email||'Belum diatur';
    $('contactWhatsAppStatus').textContent=phone?'Tersedia':'Belum Diatur';
    $('contactPhoneStatus').textContent=phone?'Tersedia':'Belum Diatur';
    $('contactEmailStatus').textContent=email?'Tersedia':'Belum Diatur';
    $('contactWhatsAppStatus').classList.toggle('available',!!phone);
    $('contactPhoneStatus').classList.toggle('available',!!phone);
    $('contactEmailStatus').classList.toggle('available',!!email);
    $('contactWhatsAppBtn').disabled=!phone;
    $('contactPhoneBtn').disabled=!phone;
    $('contactEmailBtn').disabled=!email;
  }

  function normalizeWhatsApp(phone){
    let value=String(phone||'').replace(/\D/g,'');
    if(value.startsWith('0')) value='62'+value.slice(1);
    return value;
  }

  function openContact(type){
    if(type==='whatsapp'){
      const phone=normalizeWhatsApp(settings.phone);
      if(!phone){ toast('Nomor WhatsApp kantor belum diatur.'); return; }
      location.href=`https://wa.me/${phone}?text=${encodeURIComponent('Halo '+settings.office)}`;
    }
    if(type==='phone'){
      if(!settings.phone){ toast('Nomor telepon kantor belum diatur.'); return; }
      location.href=`tel:${settings.phone.replace(/\s/g,'')}`;
    }
    if(type==='email'){
      if(!settings.email){ toast('Email kantor belum diatur.'); return; }
      location.href=`mailto:${settings.email}?subject=${encodeURIComponent('Kontak '+settings.office)}`;
    }
  }

  async function saveFeedback(e){
    e.preventDefault();
    const item={
      category:$('feedbackCategory').value,
      title:$('feedbackTitle').value.trim(),
      message:$('feedbackMessage').value.trim()
    };
    if(!item.title||!item.message){ toast('Judul dan pesan wajib diisi.'); return; }
    try{
      const data=await busy(e.submitter,()=>api('POST','/api/feedbacks',item));
      if(!data) return;
      feedbacks.unshift(data.feedback);
    }catch(error){ reportApiError(error); return; }
    $('feedbackForm').reset();
    renderFeedbacks();
    toast('Masukan berhasil dikirim.');
  }

  async function deleteFeedback(id){
    try{ await api('DELETE',`/api/feedbacks/${encodeURIComponent(id)}`); }
    catch(error){ reportApiError(error); return; }
    feedbacks=feedbacks.filter(x=>x.id!==id);
    renderFeedbacks();
    toast('Masukan dihapus.');
  }

  function renderFeedbacks(){
    $('feedbackList').innerHTML=feedbacks.length ? feedbacks.map(f=>`
      <div class="feedback-item">
        <div class="feedback-head"><div><strong>${esc(f.title)}</strong><div class="feedback-meta">${esc(f.category)} • ${fmtDateTime(f.createdAt)}</div></div><button class="action-btn danger-action" type="button" data-feedback-delete="${f.id}" title="Hapus">${uiIcon('trash')}</button></div>
        <p>${esc(f.message)}</p>
      </div>`).join('') : '<div class="empty" style="padding:24px 10px">Belum ada masukan tersimpan.</div>';
    renderSettingsMenuNotes();
  }

  function setRating(score){
    selectedRating=Number(score)||0;
    document.querySelectorAll('.star-btn').forEach(btn=>btn.classList.toggle('active',Number(btn.dataset.rating)<=selectedRating));
  }

  function renderRating(){
    selectedRating=Number(rating.score)||0;
    $('ratingComment').value=rating.comment||'';
    setRating(selectedRating);
    $('ratingSavedText').textContent=rating.score
      ? `Rating ${rating.score}/5 tersimpan${rating.updatedAt?' • '+fmtDateTime(rating.updatedAt):''}.`
      : 'Belum ada rating tersimpan.';
    renderSettingsMenuNotes();
  }

  async function saveRatingForm(e){
    e.preventDefault();
    if(!selectedRating){ toast('Pilih rating 1–5 bintang.'); return; }
    try{
      const data=await busy(e.submitter,()=>api('PUT','/api/rating',{score:selectedRating,comment:$('ratingComment').value.trim()}));
      if(!data) return;
      rating={...rating,...data.rating};
    }catch(error){ reportApiError(error); return; }
    renderRating();
    toast('Rating aplikasi berhasil disimpan.');
  }

  async function lockApp(){
    try{
      const data=await api('POST','/lock');
      location.href=data.redirect;
    }catch(error){ reportApiError(error); }
  }

  function applySettings(){
    $('brandOffice').textContent=settings.office;
    $('officeInfoOffice').textContent=settings.office;
    $('officeInfoAddress').textContent=settings.address;
    $('officeInfoHours').textContent=settings.hours;
    $('officeInfoOfficer').textContent=settings.officer;
    $('officeInfoPhone').textContent=settings.phone||'Belum diatur';
    $('officeInfoEmail').textContent=settings.email||'Belum diatur';
    $('printOffice').textContent=`BUKU TAMU ${settings.office.toUpperCase()}`;
    $('settingOffice').value=settings.office;
    $('settingHours').value=settings.hours;
    $('settingAddress').value=settings.address;
    $('settingOfficer').value=settings.officer;
    $('settingPhone').value=settings.phone;
    $('settingEmail').value=settings.email||'';
    $('settingTheme').value=settings.theme||'system';
    $('settingDateFormat').value=settings.dateFormat||'long';
    $('settingTimeFormat').value=settings.timeFormat||'24';
    $('settingDefaultPeople').value=Math.max(1,Number(settings.defaultPeople)||1);
    $('settingGuestVoiceEnabled').checked=settings.guestVoiceEnabled!==false;
    applyGuestFieldSettings();
    renderContact();
    renderSettingsMenuNotes();
  }

  function applyPreferredTheme(){
    if(settings.theme==='system'){
      applyTheme(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');
    }else{
      applyTheme(settings.theme||'light');
    }
  }

  function renderDashboard(){
    const today = guests.filter(g=>sameDay(g.checkIn));
    const inside = guests.filter(g=>!g.checkOut);
    $('statToday').textContent=today.length;
    $('statInside').textContent=inside.length;
    $('statDone').textContent=today.filter(g=>g.checkOut).length;
    $('statMonth').textContent=guests.filter(g=>sameMonth(g.checkIn)).length;

    const recent=[...guests].sort((a,b)=>new Date(b.checkIn)-new Date(a.checkIn)).slice(0,6);
    els.recentBody.innerHTML=recent.map(g=>`
      <tr>
        <td><div class="guest-cell">${guestIdentityMarkup(g)}</div></td>
        <td><div>${esc(g.purpose)}</div><div class="subtext">Bertemu: ${esc(g.meet)}</div></td>
        <td>${fmtDateTime(g.checkIn)}</td>
        <td>${statusBadge(g)}</td>
        <td><div class="actions"><button class="action-btn" title="Detail" aria-label="Detail ${esc(g.name)}" onclick="app.detail('${g.id}')">${uiIcon('eye')}</button>${!g.checkOut?`<button class="action-btn success-action requires-guest-write" title="Check-out" aria-label="Check-out ${esc(g.name)}" onclick="app.checkout('${g.id}')">${uiIcon('checkout')}</button>`:''}</div></td>
      </tr>`).join('');

    $('recentMobileList').innerHTML=recent.map(g=>`
      <article class="recent-mobile-card">
        <div class="recent-mobile-head">
          <div class="recent-mobile-main">${guestIdentityMarkup(g)}</div>
          ${statusBadge(g)}
        </div>
        <div class="recent-mobile-purpose"><span>Keperluan</span><strong>${esc(g.purpose)}</strong><div class="subtext">Bertemu: ${esc(g.meet)}</div></div>
        <div class="recent-mobile-meta"><span>Masuk ${fmtDateTime(g.checkIn)}</span><span>${g.people||1} orang</span></div>
        <div class="recent-mobile-actions">
          <button class="guest-mobile-action" type="button" onclick="app.detail('${g.id}')">${uiIcon('eye',16)}<span>Detail</span></button>
          ${!g.checkOut?`<button class="guest-mobile-action checkout requires-guest-write" type="button" onclick="app.checkout('${g.id}')">${uiIcon('checkout',16)}<span>Check-out</span></button>`:`<button class="guest-mobile-action" type="button" onclick="location.hash='#tamu'">${uiIcon('check',16)}<span>Selesai</span></button>`}
        </div>
      </article>`).join('');
    els.recentEmpty.style.display=recent.length?'none':'block';

    const acts=[];
    guests.forEach(g=>{
      acts.push({time:g.checkIn,type:'checkin',label:'Check-in',text:`${g.name} datang berkunjung.`});
      if(g.checkOut) acts.push({time:g.checkOut,type:'checkout',label:'Check-out',text:`${g.name} selesai berkunjung.`});
    });
    acts.sort((a,b)=>new Date(b.time)-new Date(a.time));
    els.activityList.innerHTML=acts.slice(0,6).map(a=>`
      <div class="activity-item"><div class="activity-dot"></div><div><span class="activity-type ${a.type}">${a.label}</span><div class="activity-main">${esc(a.text)}</div><div class="activity-time">${fmtDateTime(a.time)}</div></div></div>
    `).join('') || `<div class="empty" style="padding:14px 0">Belum ada aktivitas.</div>`;
    applyAccessControl(true);
  }

  function statusBadge(g){
    return g.checkOut
      ? '<span class="badge badge-out">Selesai</span>'
      : '<span class="badge badge-in">Di Dalam</span>';
  }

  function filteredGuests(){
    const q=$('searchInput').value.trim().toLowerCase();
    const date=$('dateFilter').value;
    const status=$('statusFilter').value;
    return [...guests].filter(g=>{
      const hay=[g.name,g.phone,g.company,g.purpose,g.meet,g.vehicle].join(' ').toLowerCase();
      const matchQ=!q||hay.includes(q);
      const matchDate=!date||toLocalInput(new Date(g.checkIn))===date;
      const matchStatus=!status||(status==='in'?!g.checkOut:!!g.checkOut);
      return matchQ&&matchDate&&matchStatus;
    }).sort((a,b)=>new Date(b.checkIn)-new Date(a.checkIn));
  }

  function renderGuests(){
    const list=filteredGuests();
    els.guestBody.innerHTML=list.map((g,i)=>`
      <tr>
        <td class="col-no">${i+1}</td>
        <td><div class="guest-cell">${guestIdentityMarkup(g)}</div></td>
        <td>${esc(g.company||'-')}</td>
        <td class="purpose-cell"><div>${esc(g.purpose)}</div><div class="subtext">${g.people||1} orang</div></td>
        <td class="meet-cell">${esc(g.meet)}</td>
        <td class="col-time">${fmtDateTime(g.checkIn)}</td>
        <td class="col-time">${fmtDateTime(g.checkOut)}</td>
        <td class="col-status">${statusBadge(g)}</td>
        <td class="col-actions"><div class="actions">
          <button class="action-btn" title="Detail" aria-label="Detail ${esc(g.name)}" onclick="app.detail('${g.id}')">${uiIcon('eye')}</button>
          <button class="action-btn requires-guest-write" title="Edit" aria-label="Edit ${esc(g.name)}" onclick="app.edit('${g.id}')">${uiIcon('edit')}</button>
          ${!g.checkOut?`<button class="action-btn success-action requires-guest-write" title="Check-out" aria-label="Check-out ${esc(g.name)}" onclick="app.checkout('${g.id}')">${uiIcon('checkout')}</button>`:''}
          <button class="action-btn danger-action requires-guest-write" title="Hapus" aria-label="Hapus ${esc(g.name)}" onclick="app.remove('${g.id}')">${uiIcon('trash')}</button>
        </div></td>
      </tr>`).join('');

    $('guestMobileList').innerHTML=list.map(g=>`
      <article class="guest-mobile-card">
        <div class="guest-mobile-head">
          <div class="guest-mobile-person">${guestIdentityMarkup(g)}</div>
          ${statusBadge(g)}
        </div>
        <div class="guest-mobile-info">
          <div class="guest-mobile-info-item wide"><span>Keperluan</span><strong>${esc(g.purpose)}</strong><div class="subtext">${g.people||1} orang</div></div>
          <div class="guest-mobile-info-item"><span>Bertemu</span><strong>${esc(g.meet)}</strong></div>
          <div class="guest-mobile-info-item"><span>Instansi</span><strong>${esc(g.company||'-')}</strong></div>
        </div>
        <div class="guest-mobile-times">
          <div class="guest-mobile-info-item"><span>Masuk</span><strong>${fmtDateTime(g.checkIn)}</strong></div>
          <div class="guest-mobile-info-item"><span>Keluar</span><strong>${fmtDateTime(g.checkOut)}</strong></div>
        </div>
        <div class="guest-mobile-actions ${g.checkOut?'three':''}">
          <button class="guest-mobile-action" onclick="app.detail('${g.id}')">${uiIcon('eye',16)}<span>Detail</span></button>
          <button class="guest-mobile-action requires-guest-write" onclick="app.edit('${g.id}')">${uiIcon('edit',16)}<span>Edit</span></button>
          ${!g.checkOut?`<button class="guest-mobile-action checkout requires-guest-write" onclick="app.checkout('${g.id}')">${uiIcon('checkout',16)}<span>Keluar</span></button>`:''}
          <button class="guest-mobile-action delete requires-guest-write" onclick="app.remove('${g.id}')">${uiIcon('trash',16)}<span>Hapus</span></button>
        </div>
      </article>`).join('');

    $('guestResultCount').textContent=`${list.length} data`;
    els.guestEmpty.style.display=list.length?'none':'block';
    applyAccessControl(true);
  }

  function stopCamera(){
    if(cameraStream){
      cameraStream.getTracks().forEach(track=>track.stop());
      cameraStream=null;
    }
    const video=$('cameraPreview');
    if(video) video.srcObject=null;
    const stage=$('cameraStage');
    if(stage) stage.classList.remove('camera-on');
  }

  function setCameraFacingUI(mode){
    currentFacingMode=mode;
    $('cameraFrontBtn')?.classList.toggle('active',mode==='user');
    $('cameraBackBtn')?.classList.toggle('active',mode==='environment');
  }

  function openDeviceCameraFallback(mode=currentFacingMode){
    const input=$('photoFileInput');
    if(!input) return;
    input.value='';
    input.setAttribute('capture',mode==='user'?'user':'environment');
    input.click();
  }

  async function startCamera(mode='user'){
    setCameraFacingUI(mode);
    stopCamera();
    const stage=$('cameraStage');
    const video=$('cameraPreview');
    if(!stage || !video) return;
    if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
      toast('Kamera browser tidak tersedia. Membuka kamera perangkat.');
      openDeviceCameraFallback(mode);
      return;
    }
    try{
      cameraStream=await navigator.mediaDevices.getUserMedia({
        video:{facingMode:{ideal:mode},width:{ideal:1280},height:{ideal:960}},
        audio:false
      });
      video.srcObject=cameraStream;
      await video.play().catch(()=>{});
      stage.classList.add('camera-on');
      stage.classList.remove('has-photo');
    }catch(err){
      toast('Kamera tidak dapat diakses. Gunakan kamera bawaan perangkat.');
      openDeviceCameraFallback(mode);
    }
  }

  function compressImageData(dataUrl,maxWidth=720,quality=.74){
    return new Promise(resolve=>{
      const img=new Image();
      img.onload=()=>{
        const scale=Math.min(1,maxWidth/img.naturalWidth);
        const w=Math.max(1,Math.round(img.naturalWidth*scale));
        const h=Math.max(1,Math.round(img.naturalHeight*scale));
        const c=document.createElement('canvas');
        c.width=w;c.height=h;
        c.getContext('2d').drawImage(img,0,0,w,h);
        resolve(c.toDataURL('image/jpeg',quality));
      };
      img.onerror=()=>resolve(dataUrl);
      img.src=dataUrl;
    });
  }

  function setGuestPhoto(dataUrl=''){
    $('guestPhotoData').value=dataUrl||'';
    const preview=$('photoPreview');
    const stage=$('cameraStage');
    if(dataUrl){
      preview.src=dataUrl;
      stage.classList.add('has-photo');
      stage.classList.remove('camera-on');
      stopCamera();
      announceGuestVoiceStep('signature');
    }else{
      preview.removeAttribute('src');
      stage.classList.remove('has-photo');
    }
  }

  async function capturePhoto(){
    const video=$('cameraPreview');
    if(!cameraStream || !video.videoWidth){
      openDeviceCameraFallback(currentFacingMode);
      return;
    }
    const maxWidth=720;
    const scale=Math.min(1,maxWidth/video.videoWidth);
    const c=document.createElement('canvas');
    c.width=Math.max(1,Math.round(video.videoWidth*scale));
    c.height=Math.max(1,Math.round(video.videoHeight*scale));
    const ctx=c.getContext('2d');
    if(currentFacingMode==='user'){
      ctx.translate(c.width,0);ctx.scale(-1,1);
    }
    ctx.drawImage(video,0,0,c.width,c.height);
    setGuestPhoto(c.toDataURL('image/jpeg',.74));
    toast('Foto tamu berhasil diambil.');
  }

  function loadGuestPhotoFile(file){
    if(!file || !file.type.startsWith('image/')) return;
    const reader=new FileReader();
    reader.onload=async e=>{
      const data=await compressImageData(e.target.result);
      setGuestPhoto(data);
      toast('Foto tamu siap digunakan.');
    };
    reader.readAsDataURL(file);
  }

  function getSignaturePoint(e,canvas){
    const rect=canvas.getBoundingClientRect();
    return {
      x:(e.clientX-rect.left)*(canvas.width/rect.width),
      y:(e.clientY-rect.top)*(canvas.height/rect.height)
    };
  }

  function paintSignatureData(dataUrl){
    const canvas=$('signatureCanvas');
    if(!canvas || !dataUrl) return;
    const img=new Image();
    img.onload=()=>{
      const ctx=canvas.getContext('2d');
      ctx.clearRect(0,0,canvas.width,canvas.height);
      ctx.drawImage(img,0,0,canvas.width,canvas.height);
      signatureHasInk=true;
      $('signaturePad').classList.add('has-ink');
    };
    img.src=dataUrl;
  }

  function resizeSignatureCanvas(preserve=true){
    const canvas=$('signatureCanvas');
    if(!canvas) return;
    const rect=canvas.getBoundingClientRect();
    if(!rect.width) return;
    const saved=preserve ? $('signatureData').value : '';
    const ratio=Math.min(window.devicePixelRatio||1,2);
    canvas.width=Math.round(rect.width*ratio);
    canvas.height=Math.round(rect.height*ratio);
    const ctx=canvas.getContext('2d');
    ctx.lineWidth=2.2*ratio;
    ctx.lineCap='round';
    ctx.lineJoin='round';
    ctx.strokeStyle='#111827';
    if(saved) paintSignatureData(saved);
  }

  function clearSignature(){
    const canvas=$('signatureCanvas');
    if(canvas) canvas.getContext('2d').clearRect(0,0,canvas.width,canvas.height);
    $('signatureData').value='';
    signatureHasInk=false;
    $('signaturePad').classList.remove('has-ink');
  }

  function finishSignature(){
    if(!signatureDrawing) return;
    signatureDrawing=false;
    const canvas=$('signatureCanvas');
    if(signatureHasInk && canvas){
      $('signatureData').value=canvas.toDataURL('image/png');
      announceGuestVoiceStep('save');
    }
  }

  function restoreSignature(dataUrl=''){
    $('signatureData').value=dataUrl||'';
    signatureHasInk=!!dataUrl;
    $('signaturePad').classList.toggle('has-ink',!!dataUrl);
    setTimeout(()=>{
      resizeSignatureCanvas(false);
      if(dataUrl) paintSignatureData(dataUrl);
    },35);
  }

  const guestVoicePrompts={
    name:'Silakan isi nama lengkap tamu.',
    phone:'Silakan isi nomor HP atau WhatsApp tamu.',
    company:'Silakan isi instansi atau perusahaan. Jika tidak ada, lanjutkan.',
    email:'Silakan isi email jika diperlukan.',
    vehicle:'Silakan isi plat nomor kendaraan jika diperlukan.',
    meet:'Silakan pilih orang atau bagian yang akan ditemui.',
    manual:'Silakan isi nama orang atau bagian yang akan ditemui.',
    people:'Silakan isi jumlah pengunjung.',
    purpose:'Silakan isi keperluan kunjungan.',
    notes:'Silakan isi catatan jika diperlukan.',
    photo:'Silakan ambil foto tamu. Jika tidak diperlukan, lanjutkan ke tanda tangan.',
    signature:'Silakan isi tanda tangan tamu.',
    save:'Silakan simpan data tamu.',
    success:'Data tamu berhasil disimpan. Terima kasih.'
  };
  let guestVoiceGuideActive=false;
  let guestVoiceLastStep='';
  const guestVoiceStepField={phone:'phone',company:'company',email:'email',vehicle:'vehicle',meet:'meet',people:'people',purpose:'purpose',notes:'notes',photo:'photo',signature:'signature'};
  const guestVoiceNextStep={phone:'company',company:'email',email:'vehicle',vehicle:'meet',meet:'people',people:'purpose',purpose:'notes',notes:'photo',photo:'signature',signature:'save'};

  function nextVisibleGuestVoiceStep(step){
    let current=step;
    while(guestVoiceStepField[current] && !isGuestFieldVisible(guestVoiceStepField[current])) current=guestVoiceNextStep[current]||'save';
    return current;
  }

  function speakGuestGuide(text,force=false){
    if(settings.guestVoiceEnabled===false) return false;
    if((!guestVoiceGuideActive&&!force)||!text) return false;
    if(!('speechSynthesis' in window)||!('SpeechSynthesisUtterance' in window)) return false;
    try{
      window.speechSynthesis.cancel();
      const utterance=new window.SpeechSynthesisUtterance(text);
      utterance.lang='id-ID';
      utterance.rate=.96;
      utterance.pitch=1;
      window.speechSynthesis.speak(utterance);
      return true;
    }catch(err){
      return false;
    }
  }

  function announceGuestVoiceStep(step){
    step=nextVisibleGuestVoiceStep(step);
    if(!guestVoiceGuideActive||$('editId').value||!guestVoicePrompts[step]||guestVoiceLastStep===step) return;
    guestVoiceLastStep=step;
    speakGuestGuide(guestVoicePrompts[step]);
  }

  function startGuestVoiceGuide(){
    if(settings.guestVoiceEnabled===false) return;
    if($('editId').value) return;
    guestVoiceGuideActive=true;
    guestVoiceLastStep='';
    setTimeout(()=>announceGuestVoiceStep('name'),100);
  }

  function finishGuestVoiceGuide(){
    guestVoiceGuideActive=false;
    guestVoiceLastStep='';
  }

  function updateFormHeader(){
    const isEdit=!!$('editId').value;
    els.headerTitle.textContent=isEdit?'Edit Data Tamu':'Registrasi Tamu';
    els.headerSubtitle.textContent=isEdit?'Perbarui identitas dan keperluan kunjungan':'';
    document.title=`${els.headerTitle.textContent} • CATAMU`;
  }

  function resetForm(){
    $('guestForm').reset();
    $('editId').value='';
    $('people').value=Math.max(1,Number(settings.defaultPeople)||1);
    renderDepartmentChoices();
    $('meetManual').value='';
    stopCamera();
    setCameraFacingUI('user');
    setGuestPhoto('');
    clearSignature();
    $('photoFileInput').value='';
    if($('page-form').classList.contains('active')){ updateFormHeader(); setTimeout(()=>resizeSignatureCanvas(false),30); }
  }

  async function submitGuest(e){
    e.preventDefault();
    if(!guardCapability('guestsWrite','menyimpan data tamu')) return;
    const id=$('editId').value;
    const meetValue=resolveMeetValue();
    if(isGuestFieldRequired('meet')&&!meetValue){ toast('Pilih departemen atau isi pihak yang ditemui.'); return; }
    if(isGuestFieldRequired('photo')&&!$('guestPhotoData').value){ toast('Foto tamu wajib diisi.'); return; }
    if(isGuestFieldRequired('signature')&&!$('signatureData').value){ toast('Tanda tangan tamu wajib diisi.'); return; }
    const data={
      name:$('name').value.trim(),
      phone:$('phone').value.trim(),
      company:$('company').value.trim(),
      email:$('email').value.trim(),
      vehicle:$('vehicle').value.trim().toUpperCase(),
      meet:meetValue,
      departmentId:resolveMeetDepartmentId(),
      purpose:$('purpose').value.trim(),
      people:Math.max(1,Number($('people').value)||1),
      notes:$('notes').value.trim(),
      photo:$('guestPhotoData').value||'',
      signature:$('signatureData').value||''
    };
    let result;
    try{
      const submitButton=$('guestForm').querySelector('button[type="submit"]');
      result=await busy(submitButton,()=>api(id?'PUT':'POST',id?`/api/guests/${encodeURIComponent(id)}`:'/api/guests',data));
      if(!result) return;
    }catch(error){ reportApiError(error); return; }
    upsertById(guests,result.guest);
    receiveNotification(result.notification);
    if(id){
      toast('Data tamu berhasil diperbarui.');
    }else{
      toast('Tamu berhasil check-in.');
      speakGuestGuide(guestVoicePrompts.success,true);
      finishGuestVoiceGuide();
    }
    resetForm(); renderDashboard(); go('guests');
  }

  function editGuest(id){
    if(!guardCapability('guestsWrite','mengedit data tamu')) return;
    const g=guests.find(x=>x.id===id); if(!g)return;
    $('editId').value=g.id; $('name').value=g.name; $('phone').value=g.phone; $('company').value=g.company||''; $('email').value=g.email||''; $('vehicle').value=g.vehicle||'';
    setMeetValue(g.meet||'',g.departmentId||'');
    $('purpose').value=g.purpose||''; $('people').value=g.people||1; $('notes').value=g.notes||'';
    setGuestPhoto(g.photo||'');
    restoreSignature(g.signature||'');
    go('form');
  }

  function wrapCanvasText(ctx,text,maxWidth){
    const source=String(text??'-').trim()||'-';
    const words=source.split(/\s+/);
    const lines=[];
    let line='';
    words.forEach(word=>{
      const trial=line?`${line} ${word}`:word;
      if(line && ctx.measureText(trial).width>maxWidth){ lines.push(line); line=word; }
      else line=trial;
    });
    if(line) lines.push(line);
    return lines.length?lines:['-'];
  }

  function loadCanvasImage(src){
    return new Promise(resolve=>{
      if(!src){resolve(null);return;}
      const img=new Image();
      img.onload=()=>resolve(img);
      img.onerror=()=>resolve(null);
      img.src=src;
    });
  }

  function drawImageContain(ctx,img,x,y,w,h){
    if(!img)return;
    const ratio=Math.min(w/img.naturalWidth,h/img.naturalHeight);
    const dw=img.naturalWidth*ratio,dh=img.naturalHeight*ratio;
    ctx.drawImage(img,x+(w-dw)/2,y+(h-dh)/2,dw,dh);
  }

  function canvasToPngBlob(canvas){
    return new Promise((resolve,reject)=>{
      canvas.toBlob(blob=>blob?resolve(blob):reject(new Error('PNG gagal dibuat.')),'image/png');
    });
  }

  async function createGuestDetailPng(guest,format){
    const is58=format==='58';
    const width=is58?384:1240;
    const canvas=document.createElement('canvas');
    const probe=canvas.getContext('2d');

    const details=[
      ['Nama Tamu',guest.name||'-'],
      ['No. HP',guest.phone||'-'],
      ['Instansi',guest.company||'-'],
      ['Bertemu',guest.meet||'-'],
      ['Jumlah Pengunjung',`${guest.people||1} orang`],
      ['Status',guest.checkOut?'Selesai':'Sedang Berkunjung'],
      ['Check-in',fmtDateTime(guest.checkIn)],
      ['Check-out',fmtDateTime(guest.checkOut)],
      ['Keperluan',guest.purpose||'-'],
      ['Catatan',guest.notes||'-'],
      ['Catatan Check-out',guest.checkoutNote||'-']
    ].filter(([,v])=>String(v||'-').trim()!=='' && String(v||'-').trim()!=='-');

    const generatedAt = new Intl.DateTimeFormat('id-ID',{
      day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit',hour12:false
    }).format(new Date());

    function roundRect(ctx,x,y,w,h,r,fill,stroke){
      const radius=Math.min(r,w/2,h/2);
      ctx.beginPath();
      ctx.moveTo(x+radius,y);
      ctx.arcTo(x+w,y,x+w,y+h,radius);
      ctx.arcTo(x+w,y+h,x,y+h,radius);
      ctx.arcTo(x,y+h,x,y,radius);
      ctx.arcTo(x,y,x+w,y,radius);
      ctx.closePath();
      if(fill) ctx.fill();
      if(stroke) ctx.stroke();
    }

    function textLines(font,text,maxWidth){
      probe.font=font;
      return wrapCanvasText(probe,text,maxWidth);
    }

    if(is58){
      const margin=20;
      const contentWidth=width-(margin*2);
      const labelFont='700 12px Arial';
      const valueFont='700 18px Arial';
      const smallFont='600 11px Arial';
      let measuredHeight=28+26+26+18;
      for(const [label,value] of details){
        measuredHeight += 18;
        measuredHeight += textLines(valueFont,String(value),contentWidth).length*22;
        measuredHeight += 10;
      }
      const hasPhoto=!!guest.photo, hasSignature=!!guest.signature;
      if(hasPhoto) measuredHeight += 24 + 210 + 14;
      if(hasSignature) measuredHeight += 24 + 160 + 14;
      measuredHeight += 44 + 22 + 30;
      canvas.width=width;
      canvas.height=Math.max(760, measuredHeight);
      const ctx=canvas.getContext('2d');
      const height=canvas.height;
      ctx.fillStyle='#ffffff';ctx.fillRect(0,0,width,height);
      ctx.textBaseline='top';

      let y=20;
      ctx.textAlign='center';
      ctx.fillStyle='#111827';
      ctx.font='900 28px Arial';
      ctx.fillText('CATAMU',width/2,y); y+=30;
      ctx.fillStyle='#dc2626';
      ctx.font='800 15px Arial';
      ctx.fillText('DETAIL TAMU',width/2,y); y+=22;
      ctx.fillStyle='#6b7280';
      ctx.font='600 12px Arial';
      textLines('600 12px Arial',settings.office||'Kantor',contentWidth).forEach(line=>{ctx.fillText(line,width/2,y); y+=14;});
      y+=6;
      ctx.strokeStyle='#111827'; ctx.lineWidth=1.5;
      ctx.setLineDash([4,4]); ctx.beginPath(); ctx.moveTo(margin,y); ctx.lineTo(width-margin,y); ctx.stroke(); ctx.setLineDash([]);
      y+=12;

      ctx.textAlign='left';
      for(const [label,value] of details){
        ctx.fillStyle='#6b7280';
        ctx.font=labelFont;
        ctx.fillText(label.toUpperCase(),margin,y); y+=15;

        if(label==='Status'){
          const pillText=String(value);
          ctx.font='800 12px Arial';
          const pillW=Math.min(contentWidth,ctx.measureText(pillText).width+22);
          ctx.fillStyle=pillText==='Selesai'?'#dcfce7':'#fee2e2';
          ctx.strokeStyle=pillText==='Selesai'?'#86efac':'#fca5a5';
          ctx.lineWidth=1;
          roundRect(ctx,margin,y,pillW,24,12,true,true);
          ctx.fillStyle=pillText==='Selesai'?'#166534':'#991b1b';
          ctx.textAlign='center';
          ctx.fillText(pillText,margin+(pillW/2),y+5);
          ctx.textAlign='left';
          y+=30;
        }else{
          ctx.fillStyle='#111827';
          ctx.font=valueFont;
          const lines=textLines(valueFont,String(value),contentWidth);
          lines.forEach(line=>{ctx.fillText(line,margin,y); y+=20;});
          y+=6;
        }
        ctx.strokeStyle='#e5e7eb'; ctx.lineWidth=1; ctx.beginPath(); ctx.moveTo(margin,y); ctx.lineTo(width-margin,y); ctx.stroke();
        y+=10;
      }

      const [photoImg,signatureImg]=await Promise.all([loadCanvasImage(guest.photo),loadCanvasImage(guest.signature)]);
      if(hasPhoto){
        ctx.fillStyle='#6b7280'; ctx.font=labelFont; ctx.fillText('FOTO TAMU',margin,y); y+=18;
        ctx.fillStyle='#f8fafc'; ctx.strokeStyle='#d1d5db'; ctx.lineWidth=1;
        roundRect(ctx,margin,y,contentWidth,210,16,true,true);
        if(photoImg) drawImageContain(ctx,photoImg,margin+8,y+8,contentWidth-16,194);
        y+=220;
      }
      if(hasSignature){
        ctx.fillStyle='#6b7280'; ctx.font=labelFont; ctx.fillText('TANDA TANGAN',margin,y); y+=18;
        ctx.fillStyle='#f8fafc'; ctx.strokeStyle='#d1d5db';
        roundRect(ctx,margin,y,contentWidth,160,16,true,true);
        if(signatureImg) drawImageContain(ctx,signatureImg,margin+8,y+8,contentWidth-16,144);
        y+=170;
      }

      ctx.strokeStyle='#111827'; ctx.lineWidth=1.5;
      ctx.setLineDash([4,4]); ctx.beginPath(); ctx.moveTo(margin,y); ctx.lineTo(width-margin,y); ctx.stroke(); ctx.setLineDash([]);
      y+=12;
      ctx.textAlign='center';
      ctx.fillStyle='#6b7280'; ctx.font=smallFont;
      ctx.fillText('Dicetak: '+generatedAt,width/2,y); y+=14;
      ctx.fillText('Dokumen dibuat dari CATAMU',width/2,y);
      return canvasToPngBlob(canvas);
    }

    const pageW=width, pageH=1754;
    canvas.width=pageW; canvas.height=pageH;
    const ctx=canvas.getContext('2d');
    ctx.fillStyle='#f3f4f6'; ctx.fillRect(0,0,pageW,pageH);
    ctx.textBaseline='top';
    const pageMargin=70;
    const cardX=pageMargin, cardY=58, cardW=pageW-(pageMargin*2), cardH=pageH-116;
    ctx.fillStyle='#ffffff'; ctx.strokeStyle='#e5e7eb'; ctx.lineWidth=2;
    roundRect(ctx,cardX,cardY,cardW,cardH,30,true,true);

    ctx.fillStyle='#991b1b';
    roundRect(ctx,cardX,cardY,cardW,180,30,true,false);
    ctx.fillStyle='#ffffff';
    ctx.font='900 46px Arial'; ctx.fillText('CATAMU',cardX+48,cardY+38);
    ctx.font='700 24px Arial'; ctx.fillText(settings.office||'Kantor',cardX+48,cardY+98);
    ctx.font='800 34px Arial'; ctx.textAlign='right'; ctx.fillText('DETAIL TAMU',cardX+cardW-48,cardY+56);
    ctx.font='600 18px Arial'; ctx.fillText(generatedAt,cardX+cardW-48,cardY+104);
    ctx.textAlign='left';

    const contentX=cardX+44, contentY=cardY+210, contentW=cardW-88;
    const leftW=760, rightW=contentW-leftW-28;

    function drawSection(title,x,y,w,rows){
      const sectionPadding=22;
      const titleBandH=52;
      const innerW=w-(sectionPadding*2);
      let bodyH=0;
      rows.forEach(([label,value])=>{
        bodyH += 18;
        bodyH += textLines('700 24px Arial',String(value||'-'),innerW).length*30;
        bodyH += 18;
      });
      const sectionH=titleBandH+bodyH+12;
      ctx.fillStyle='#ffffff'; ctx.strokeStyle='#e5e7eb'; ctx.lineWidth=1.5;
      roundRect(ctx,x,y,w,sectionH,22,true,true);
      ctx.fillStyle='#f9fafb';
      roundRect(ctx,x,y,w,titleBandH,22,true,false);
      ctx.fillStyle='#111827'; ctx.font='800 22px Arial';
      ctx.fillText(title,x+sectionPadding,y+14);
      let yy=y+titleBandH+16;
      rows.forEach(([label,value],idx)=>{
        ctx.fillStyle='#6b7280'; ctx.font='700 14px Arial';
        ctx.fillText(label.toUpperCase(),x+sectionPadding,yy); yy+=18;
        ctx.fillStyle='#111827'; ctx.font='700 24px Arial';
        const lines=textLines('700 24px Arial',String(value||'-'),innerW);
        lines.forEach(line=>{ctx.fillText(line,x+sectionPadding,yy); yy+=30;});
        yy+=12;
        if(idx<rows.length-1){ctx.strokeStyle='#eef2f7'; ctx.beginPath(); ctx.moveTo(x+sectionPadding,yy); ctx.lineTo(x+w-sectionPadding,yy); ctx.stroke(); yy+=12;}
      });
      return sectionH;
    }

    const leftInfo=[
      ['Nama Tamu',guest.name||'-'],
      ['No. HP / WhatsApp',guest.phone||'-'],
      ['Instansi / Perusahaan',guest.company||'-'],
      ['Bertemu / Departemen',guest.meet||'-']
    ];
    const visitInfo=[
      ['Jumlah Pengunjung',`${guest.people||1} orang`],
      ['Check-in',fmtDateTime(guest.checkIn)],
      ['Check-out',fmtDateTime(guest.checkOut)],
      ['Status',guest.checkOut?'Selesai':'Sedang Berkunjung']
    ];
    const notesInfo=[
      ['Keperluan',guest.purpose||'-'],
      ['Catatan',guest.notes||'-'],
      ['Catatan Check-out',guest.checkoutNote||'-']
    ].filter(([,v])=>String(v||'-').trim()!=='' && String(v||'-').trim()!=='-');

    const leftTopH=drawSection('Informasi Tamu',contentX,contentY,leftW,leftInfo);
    const leftBottomY=contentY+leftTopH+24;
    const visitH=drawSection('Informasi Kunjungan',contentX,leftBottomY,leftW,visitInfo);
    const notesY=leftBottomY+visitH+24;
    drawSection('Keperluan & Catatan',contentX,notesY,leftW,notesInfo.length?notesInfo:[['Catatan','-']]);

    const [photoImg,signatureImg]=await Promise.all([loadCanvasImage(guest.photo),loadCanvasImage(guest.signature)]);
    function mediaCard(title,x,y,w,h,img){
      ctx.fillStyle='#ffffff'; ctx.strokeStyle='#e5e7eb'; ctx.lineWidth=1.5;
      roundRect(ctx,x,y,w,h,22,true,true);
      ctx.fillStyle='#f9fafb'; roundRect(ctx,x,y,w,48,22,true,false);
      ctx.fillStyle='#111827'; ctx.font='800 20px Arial'; ctx.fillText(title,x+18,y+13);
      const innerY=y+60;
      ctx.fillStyle='#f8fafc'; ctx.strokeStyle='#d1d5db';
      roundRect(ctx,x+18,innerY,w-36,h-78,18,true,true);
      if(img){ drawImageContain(ctx,img,x+28,innerY+10,w-56,h-98); }
      else { ctx.fillStyle='#9ca3af'; ctx.font='700 20px Arial'; ctx.textAlign='center'; ctx.fillText('Tidak tersedia',x+(w/2),innerY+((h-78)/2)-12); ctx.textAlign='left'; }
    }

    mediaCard('Foto Tamu',contentX+leftW+28,contentY,rightW,380,photoImg);
    mediaCard('Tanda Tangan',contentX+leftW+28,contentY+404,rightW,300,signatureImg);

    const statusText=guest.checkOut?'Status: Selesai':'Status: Sedang Berkunjung';
    ctx.fillStyle=guest.checkOut?'#dcfce7':'#fee2e2';
    ctx.strokeStyle=guest.checkOut?'#86efac':'#fca5a5';
    roundRect(ctx,contentX+leftW+28,contentY+726,320,54,27,true,true);
    ctx.fillStyle=guest.checkOut?'#166534':'#991b1b'; ctx.font='800 22px Arial'; ctx.textAlign='center'; ctx.fillText(statusText,contentX+leftW+28+160,contentY+742);
    ctx.textAlign='left';

    ctx.fillStyle='#6b7280'; ctx.font='600 18px Arial'; ctx.textAlign='center';
    ctx.fillText('Dokumen dibuat dari CATAMU • '+generatedAt,pageW/2,pageH-82);
    return canvasToPngBlob(canvas);
  }

  async function shareGuestDetailPng(format){

    const guest=guests.find(g=>g.id===currentDetailGuestId);
    if(!guest){toast('Data tamu tidak ditemukan.');return;}
    const label=format==='58'?'58mm':'A4';
    const safeName=String(guest.name||'tamu').replace(/[^a-z0-9]+/gi,'-').replace(/^-|-$/g,'').toLowerCase()||'tamu';
    const filename=`catamu-detail-${safeName}-${label.toLowerCase()}.png`;
    try{
      toast(`Membuat PNG ${label}...`);
      const blob=await createGuestDetailPng(guest,format);
      const file=new File([blob],filename,{type:'image/png'});
      if(navigator.share && navigator.canShare && navigator.canShare({files:[file]})){
        try{
          await navigator.share({title:`Detail Tamu - ${guest.name}`,text:'Detail kunjungan dari CATAMU',files:[file]});
          toast(`PNG ${label} berhasil dibagikan.`);
          return;
        }catch(err){
          if(err && err.name==='AbortError')return;
        }
      }
      const url=URL.createObjectURL(blob);
      const a=document.createElement('a');a.href=url;a.download=filename;a.click();
      setTimeout(()=>URL.revokeObjectURL(url),1000);
      toast(`PNG ${label} berhasil dibuat.`);
    }catch(err){
      console.error(err);toast('PNG detail tamu gagal dibuat.');
    }
  }

  function detailGuest(id){
    const access=currentAccess();
    if(!(access.guestsView||access.reports)){ toast('Akses detail tamu tidak diizinkan untuk role Anda.'); return; }
    const g=guests.find(x=>x.id===id); if(!g)return;
    currentDetailGuestId=id;
    const fields=[
      ['Nama Lengkap',g.name],['Nomor HP',g.phone],['Instansi / Perusahaan',g.company||'-'],['Email',g.email||'-'],
      ['Bertemu',g.meet],
      ['Keperluan',g.purpose],['Jumlah Pengunjung',(g.people||1)+' orang'],['Plat Nomor',g.vehicle||'-'],
      ['Waktu Check-in',fmtDateTime(g.checkIn)],['Waktu Check-out',fmtDateTime(g.checkOut)],
      ['Status',g.checkOut?'Selesai':'Sedang Berkunjung'],['Catatan',g.notes||'-'],
      ['Catatan Check-out',g.checkoutNote||'-']
    ];
    const media=`<div class="detail-media-grid">
      <div class="detail-media"><span>Foto Tamu</span>${g.photo?`<img src="${esc(g.photo)}" alt="Foto ${esc(g.name)}">`:'<strong>-</strong>'}</div>
      <div class="detail-media"><span>Tanda Tangan</span>${g.signature?`<img src="${esc(g.signature)}" alt="Tanda tangan ${esc(g.name)}">`:'<strong>-</strong>'}</div>
    </div>`;
    $('detailBody').innerHTML=`<div class="detail-grid">${fields.map(([a,b])=>`<div class="detail-item"><span>${esc(a)}</span><strong>${esc(b)}</strong></div>`).join('')}</div>${media}`;
    $('detailModal').classList.add('show');
  }

  function checkoutGuest(id){
    if(!guardCapability('guestsWrite','melakukan check-out tamu')) return;
    const g=guests.find(x=>x.id===id); if(!g||g.checkOut)return;
    $('checkoutId').value=id; $('checkoutNote').value=''; $('checkoutModal').classList.add('show');
  }

  async function confirmCheckout(){
    if(!guardCapability('guestsWrite','melakukan check-out tamu')) return;
    const id=$('checkoutId').value;
    const g=guests.find(x=>x.id===id); if(!g)return;
    let result;
    try{
      result=await busy($('confirmCheckout'),()=>api('POST',`/api/guests/${encodeURIComponent(id)}/checkout`,{note:$('checkoutNote').value.trim()}));
      if(!result) return;
    }catch(error){ reportApiError(error); return; }
    upsertById(guests,result.guest);
    receiveNotification(result.notification);
    closeModal('checkoutModal'); renderDashboard(); renderGuests(); toast(`${result.guest.name} berhasil check-out.`);
  }

  async function removeGuest(id){
    if(!guardCapability('guestsWrite','menghapus data tamu')) return;
    const g=guests.find(x=>x.id===id); if(!g)return;
    if(!confirm(`Hapus data tamu "${g.name}"?`))return;
    try{ await api('DELETE',`/api/guests/${encodeURIComponent(id)}`); }
    catch(error){ reportApiError(error); return; }
    guests=guests.filter(x=>x.id!==id); renderDashboard(); renderGuests(); toast('Data tamu telah dihapus.');
  }

  function closeModal(id){
    $(id).classList.remove('show');
    if(id==='qrisRenewalModal') resetQrisPaymentProof();
    if(id==='teamModal') resetTeamForm();
  }

  function exportCSV(){
    if(!guardCapability('reports','mengekspor laporan',false)) return;
    const list=filteredGuests();
    if(!list.length){toast('Tidak ada data untuk diekspor.');return;}
    const headers=['No','Nama','Nomor HP','Instansi','Email','Bertemu','Keperluan','Jumlah Pengunjung','Plat Nomor','Check-in','Check-out','Status','Catatan'];
    const rows=list.map((g,i)=>[i+1,g.name,g.phone,g.company,g.email||'',g.meet,g.purpose,g.people,g.vehicle||'',fmtDateTime(g.checkIn),fmtDateTime(g.checkOut),g.checkOut?'Selesai':'Di Dalam',g.notes]);
    const csv=[headers,...rows].map(row=>row.map(v=>`"${String(v??'').replace(/"/g,'""')}"`).join(',')).join('\r\n');
    const blob=new Blob(['\ufeff'+csv],{type:'text/csv;charset=utf-8'});
    const url=URL.createObjectURL(blob); const a=document.createElement('a');
    a.href=url; a.download=`buku-tamu-${toLocalInput()}.csv`; a.click(); URL.revokeObjectURL(url);
    toast('Data CSV berhasil dibuat.');
  }

  let clockTimer=0;
  function updateClock(){
    const d=new Date();
    const hour12=settings.timeFormat==='12';
    $('clock').textContent=new Intl.DateTimeFormat('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12}).format(d);
    $('dateText').textContent=new Intl.DateTimeFormat('id-ID',settings.dateFormat==='short'
      ? {day:'2-digit',month:'2-digit',year:'numeric'}
      : {weekday:'long',day:'2-digit',month:'long',year:'numeric'}).format(d);
    $('printDate').textContent=`Dicetak: ${new Intl.DateTimeFormat('id-ID',{
      day:'2-digit',month:settings.dateFormat==='short'?'2-digit':'long',year:'numeric',
      hour:'2-digit',minute:'2-digit',hour12
    }).format(d)}`;
    applyAccessControl();
  }

  function syncClockTicker(){
    if(clockTimer){ clearInterval(clockTimer); clockTimer=0; }
    if(document.hidden) return;
    clockTimer=setInterval(()=>updateClock(),1000);
  }

  function applyTheme(theme){
    document.body.classList.toggle('dark',theme==='dark');
    $('themeBtn').innerHTML=uiIcon(theme==='dark'?'sun':'moon');
    $('themeBtn').setAttribute('aria-label',theme==='dark'?'Gunakan tema terang':'Gunakan tema gelap');
  }

  document.querySelectorAll('.nav-btn').forEach(b=>b.addEventListener('click',()=>{
    if(b.dataset.page==='form'){
      resetForm();
      go('form');
      startGuestVoiceGuide();
      return;
    }
    finishGuestVoiceGuide();
    go(b.dataset.page);
  }));
  document.querySelectorAll('.bottom-nav-btn').forEach(b=>b.addEventListener('click',()=>{
    if(b.dataset.route==='form'){
      resetForm();
      go('form');
      startGuestVoiceGuide();
      return;
    }
    finishGuestVoiceGuide();
    go(b.dataset.route);
  }));
  window.addEventListener('hashchange',routeFromHash);
  document.querySelectorAll('[data-go]').forEach(b=>b.addEventListener('click',()=>{
    if(b.dataset.go==='form'){
      resetForm();
      go('form');
      startGuestVoiceGuide();
      return;
    }
    finishGuestVoiceGuide();
    go(b.dataset.go);
  }));
  document.querySelectorAll('[data-close]').forEach(b=>b.addEventListener('click',()=>closeModal(b.dataset.close)));
  els.overlay.addEventListener('click',closeSidebar);
  $('guestForm').addEventListener('submit',submitGuest);
  $('resetFormBtn').addEventListener('click',()=>{ resetForm(); startGuestVoiceGuide(); });
  $('name').addEventListener('blur',()=>announceGuestVoiceStep('phone'));
  $('phone').addEventListener('blur',()=>announceGuestVoiceStep('company'));
  $('company').addEventListener('blur',()=>announceGuestVoiceStep('email'));
  $('email').addEventListener('blur',()=>announceGuestVoiceStep('vehicle'));
  $('vehicle').addEventListener('blur',()=>announceGuestVoiceStep('meet'));
  $('meetDepartment').addEventListener('change',()=>{
    const value=$('meetDepartment').value;
    if(value==='__manual__') announceGuestVoiceStep('manual');
    else if(value) announceGuestVoiceStep('people');
  });
  $('meetManual').addEventListener('blur',()=>announceGuestVoiceStep('people'));
  $('people').addEventListener('blur',()=>announceGuestVoiceStep('purpose'));
  $('purpose').addEventListener('blur',()=>announceGuestVoiceStep('notes'));
  $('notes').addEventListener('blur',()=>announceGuestVoiceStep('photo'));
  $('signatureCanvas').addEventListener('pointerdown',()=>announceGuestVoiceStep('signature'));
  $('guestForm').querySelector('button[type="submit"]').addEventListener('focus',()=>announceGuestVoiceStep('save'));
  $('cameraFrontBtn').addEventListener('click',()=>startCamera('user'));
  $('cameraBackBtn').addEventListener('click',()=>startCamera('environment'));
  $('capturePhotoBtn').addEventListener('click',capturePhoto);
  $('retakePhotoBtn').addEventListener('click',()=>{ setGuestPhoto(''); startCamera(currentFacingMode); });
  $('choosePhotoBtn').addEventListener('click',()=>{
    const input=$('photoFileInput'); input.removeAttribute('capture'); input.value=''; input.click();
  });
  $('photoFileInput').addEventListener('change',e=>loadGuestPhotoFile(e.target.files?.[0]));
  $('clearSignatureBtn').addEventListener('click',clearSignature);
  $('signatureCanvas').addEventListener('pointerdown',e=>{
    const canvas=e.currentTarget;
    signatureDrawing=true;
    signatureHasInk=true;
    $('signaturePad').classList.add('has-ink');
    canvas.setPointerCapture?.(e.pointerId);
    const ctx=canvas.getContext('2d');
    const p=getSignaturePoint(e,canvas);
    ctx.beginPath();ctx.moveTo(p.x,p.y);
    e.preventDefault();
  });
  $('signatureCanvas').addEventListener('pointermove',e=>{
    if(!signatureDrawing) return;
    const canvas=e.currentTarget,ctx=canvas.getContext('2d'),p=getSignaturePoint(e,canvas);
    ctx.lineTo(p.x,p.y);ctx.stroke();
    e.preventDefault();
  });
  ['pointerup','pointercancel','pointerleave'].forEach(type=>$('signatureCanvas').addEventListener(type,finishSignature));
  window.addEventListener('resize',()=>{ if($('page-form').classList.contains('active')) resizeSignatureCanvas(true); });
  $('confirmCheckout').addEventListener('click',confirmCheckout);
  $('searchInput').addEventListener('input',debounce(renderGuests,150));
  ['dateFilter','statusFilter'].forEach(id=>$(id).addEventListener('change',renderGuests));
  $('resetGuestFilters').addEventListener('click',()=>{
    $('searchInput').value='';
    $('dateFilter').value='';
    $('statusFilter').value='';
    renderGuests();
    $('searchInput').focus();
  });
  $('exportBtn').addEventListener('click',exportCSV);
  $('printBtn').addEventListener('click',()=>{ if(guardCapability('reports','mencetak laporan',false)) window.print(); });

  $('officeInfoBtn').addEventListener('click',e=>{ e.stopPropagation(); toggleOfficeInfoPanel(); });
  $('officeInfoPanel').addEventListener('click',e=>e.stopPropagation());
  $('notificationBtn').addEventListener('click',e=>{ e.stopPropagation(); toggleNotificationPanel(); });
  $('notificationPanel').addEventListener('click',e=>e.stopPropagation());
  $('notificationList').addEventListener('click',e=>{
    const item=e.target.closest('[data-notification-read]');
    if(item) markNotificationRead(item.dataset.notificationRead);
  });
  $('markAllNotificationsRead').addEventListener('click',()=>{
    let changed=false;
    notifications.forEach(n=>{ if(!n.read){ n.read=true; changed=true; } });
    if(changed) api('POST','/api/notifications/read-all').catch(reportApiError);
    renderNotifications();
  });
  $('clearNotifications').addEventListener('click',async()=>{
    if(!notifications.length){ toast('Notifikasi sudah kosong.'); return; }
    if(!confirm('Hapus seluruh notifikasi?')) return;
    try{ await api('DELETE','/api/notifications'); }
    catch(error){ reportApiError(error); return; }
    notifications=[];
    renderNotifications();
  });
  document.addEventListener('click',()=>{ closeNotificationPanel(); closeOfficeInfoPanel(); });

  $('themeBtn').addEventListener('click',()=>{
    const next=document.body.classList.contains('dark')?'light':'dark';
    settings.theme=next;
    applyTheme(next);
    $('settingTheme').value=next;
    api('PUT','/api/me/theme',{theme:next}).catch(reportApiError);
  });

  window.addEventListener('beforeinstallprompt',event=>{
    event.preventDefault();
    deferredInstallPrompt=event;
    renderPwaInstallState();
  });

  window.addEventListener('appinstalled',()=>{
    deferredInstallPrompt=null;
    renderPwaInstallState();
    toast('CATAMU berhasil dipasang.');
  });

  $('installPwaBtn').addEventListener('click',installPwaApp);

  document.querySelectorAll('[data-setting-action]').forEach(btn=>btn.addEventListener('click',()=>handleSettingAction(btn.dataset.settingAction)));
  document.querySelectorAll('[data-setting-back]').forEach(btn=>btn.addEventListener('click',()=>openSettingsView('menu')));
  document.querySelectorAll('[data-plan-days]').forEach(btn=>btn.addEventListener('click',()=>openQrisRenewalModal(Number(btn.dataset.planDays),btn.dataset.planName)));
  $('chooseQrisPaymentProofBtn').addEventListener('click',()=>{
    const input=$('qrisPaymentProofInput');
    input.value='';
    input.click();
  });
  $('qrisPaymentProofInput').addEventListener('change',e=>loadQrisPaymentProof(e.target.files?.[0]));
  $('removeQrisPaymentProofBtn').addEventListener('click',resetQrisPaymentProof);
  $('confirmQrisRenewalBtn').addEventListener('click',confirmQrisRenewal);

  $('notificationSettingsForm').addEventListener('submit',saveNotificationSettings);
  $('requestNotificationPermissionBtn').addEventListener('click',requestBrowserNotificationPermission);

  $('accountForm').addEventListener('submit',saveAccount);
  $('deleteAccountBtn').addEventListener('click',openDeleteAccountModal);
  $('deleteAccountConfirmText').addEventListener('input',updateDeleteAccountConfirmation);
  $('confirmDeleteAccountBtn').addEventListener('click',deleteAccountAndData);
  $('profilePhoto').addEventListener('change',e=>loadProfilePhoto(e.target.files?.[0]));
  $('removeProfilePhoto').addEventListener('click',async()=>{
    if(!guardCapability('accountManage','menghapus foto profil Owner',false)) return;
    try{
      const data=await api('DELETE','/api/account/photo');
      profile={...profile,...data.profile};
    }catch(error){ reportApiError(error); return; }
    renderProfileAvatar();
    $('profilePhoto').value='';
    toast('Foto profil dihapus.');
  });
  $('removePinBtn').addEventListener('click',async()=>{
    if(!guardCapability('accountManage','menghapus PIN Owner',false)) return;
    if(!profile.hasPin){ toast('PIN belum dibuat.'); return; }
    if(!confirm('Hapus PIN aplikasi? Setelah itu layar kunci Owner dibuka dengan akun Google.')) return;
    try{
      const data=await api('DELETE','/api/account/pin');
      profile={...profile,...data.profile};
    }catch(error){ reportApiError(error); return; }
    renderProfile();
    renderSettingsMenuNotes();
    toast('PIN aplikasi dihapus.');
  });

  $('teamForm').addEventListener('submit',saveTeamMember);
  $('addTeamBtn').addEventListener('click',()=>{ if(guardCapability('teamManage','menambah anggota tim')) openTeamModal('add'); });
  $('teamRole').addEventListener('change',()=>applyTeamRoleDefaults($('teamRole').value,true));
  $('teamModal').addEventListener('click',e=>{
    const toggle=e.target.closest('[data-password-toggle]');
    if(!toggle) return;
    const input=$(toggle.dataset.passwordToggle);
    if(!input) return;
    const show=input.type==='password';
    input.type=show?'text':'password';
    toggle.textContent=show?'Sembunyikan':'Tampilkan';
  });
  $('teamSearchInput').addEventListener('input',debounce(renderTeam,150));
  $('teamRoleFilter').addEventListener('change',renderTeam);
  $('teamStatusFilter').addEventListener('change',renderTeam);
  $('teamList').addEventListener('click',e=>{
    const edit=e.target.closest('[data-team-edit]');
    const del=e.target.closest('[data-team-delete]');
    if(edit) editTeamMember(edit.dataset.teamEdit);
    if(del) deleteTeamMember(del.dataset.teamDelete);
  });

  $('departmentForm').addEventListener('submit',saveDepartment);
  $('resetDepartmentBtn').addEventListener('click',resetDepartmentForm);
  $('departmentList').addEventListener('click',e=>{
    const edit=e.target.closest('[data-department-edit]');
    const del=e.target.closest('[data-department-delete]');
    if(edit) editDepartment(edit.dataset.departmentEdit);
    if(del) deleteDepartment(del.dataset.departmentDelete);
  });
  $('departmentSearchInput').addEventListener('input',debounce(renderDepartments,150));
  $('departmentStatusFilter').addEventListener('change',renderDepartments);
  $('meetDepartment').addEventListener('change',toggleMeetManual);
  $('settingGuestVoiceEnabled').addEventListener('change',e=>{
    if(e.target.checked) return;
    guestVoiceGuideActive=false;
    guestVoiceLastStep='';
    if('speechSynthesis' in window) window.speechSynthesis.cancel();
  });

  $('guestFieldSettingsForm').addEventListener('change',e=>{
    const row=e.target.closest('[data-guest-field-setting]');
    if(!row) return;
    const visible=row.querySelector('[data-field-visible]');
    const required=row.querySelector('[data-field-required]');
    if(e.target===visible && required){
      if(!visible.checked) required.checked=false;
      required.disabled=!visible.checked;
    }
  });

  $('guestFieldSettingsForm').addEventListener('submit',async e=>{
    e.preventDefault();
    if(!guardCapability('settings','mengubah field registrasi')) return;
    const next={};
    document.querySelectorAll('[data-guest-field-setting]').forEach(row=>{
      const key=row.dataset.guestFieldSetting;
      const visible=row.querySelector('[data-field-visible]');
      const required=row.querySelector('[data-field-required]');
      const base=guestFieldDefaults[key];
      const field={visible:base.locked ? true : visible.checked,required:false};
      field.required=field.visible ? required.checked : false;
      if(base.locked){ field.visible=true; field.required=true; field.locked=true; }
      next[key]=field;
    });
    try{
      const data=await busy(e.submitter,()=>api('PUT','/api/settings/guest-fields',{guestFields:normalizeGuestFieldSettings(next)}));
      if(!data) return;
      applySettingsResponse(data.settings);
    }catch(error){ reportApiError(error); return; }
    applyGuestFieldSettings();
    toast('Atur Data Tamu berhasil disimpan.');
  });

  $('resetGuestFieldSettingsBtn').addEventListener('click',async()=>{
    if(!guardCapability('settings','mereset field registrasi')) return;
    try{
      const data=await busy($('resetGuestFieldSettingsBtn'),()=>api('PUT','/api/settings/guest-fields',{guestFields:normalizeGuestFieldSettings(guestFieldDefaults)}));
      if(!data) return;
      applySettingsResponse(data.settings);
    }catch(error){ reportApiError(error); return; }
    applyGuestFieldSettings();
    toast('Atur Data Tamu dikembalikan ke pengaturan awal.');
  });

  $('settingsForm').addEventListener('submit',async e=>{
    e.preventDefault();
    if(!guardCapability('settings','mengubah pengaturan aplikasi')) return;
    const payload={
      office:$('settingOffice').value.trim()||defaults.office,
      hours:$('settingHours').value.trim()||defaults.hours,
      address:$('settingAddress').value.trim()||defaults.address,
      officer:$('settingOfficer').value.trim()||defaults.officer,
      phone:$('settingPhone').value.trim(),
      email:$('settingEmail').value.trim(),
      theme:$('settingTheme').value,
      dateFormat:$('settingDateFormat').value,
      timeFormat:$('settingTimeFormat').value,
      defaultPeople:Math.max(1,Number($('settingDefaultPeople').value)||1),
      guestVoiceEnabled:$('settingGuestVoiceEnabled').checked
    };
    try{
      const data=await busy(e.submitter,()=>api('PUT','/api/settings/application',payload));
      if(!data) return;
      applySettingsResponse(data.settings);
      if(data.links?.appUrl && data.links.appUrl!==APP_URL){
        toast('Pengaturan disimpan. Link aplikasi & cek-in mengikuti nama kantor baru.');
        setTimeout(()=>{ location.href=data.links.appUrl+location.hash; },1400);
        return;
      }
    }catch(error){ reportApiError(error); return; }
    applyPreferredTheme();
    applySettings();
    updateClock();
    renderDashboard();
    renderGuests();
    toast('Pengaturan aplikasi berhasil disimpan.');
  });

  $('clearDataBtn').addEventListener('click',async()=>{
    if(!guardCapability('settings','menghapus seluruh data tamu')) return;
    if(!guests.length){toast('Data tamu sudah kosong.');return;}
    if(!confirm('Hapus seluruh data tamu? Tindakan ini tidak dapat dibatalkan.')) return;
    try{ await busy($('clearDataBtn'),()=>api('DELETE','/api/guests')); }
    catch(error){ reportApiError(error); return; }
    guests=[];renderDashboard();renderGuests();toast('Seluruh data tamu telah dihapus.');
  });

  $('shareGuest58Btn').addEventListener('click',()=>{ if(guardCapability('reports','membagikan laporan',false)) shareGuestDetailPng('58'); });
  $('shareGuestA4Btn').addEventListener('click',()=>{ if(guardCapability('reports','membagikan laporan',false)) shareGuestDetailPng('a4'); });

  $('contactWhatsAppBtn').addEventListener('click',()=>openContact('whatsapp'));
  $('contactPhoneBtn').addEventListener('click',()=>openContact('phone'));
  $('contactEmailBtn').addEventListener('click',()=>openContact('email'));

  $('feedbackForm').addEventListener('submit',saveFeedback);
  $('feedbackList').addEventListener('click',e=>{
    const del=e.target.closest('[data-feedback-delete]');
    if(del && confirm('Hapus masukan ini?')) deleteFeedback(del.dataset.feedbackDelete);
  });

  document.querySelectorAll('.star-btn').forEach(btn=>btn.addEventListener('click',()=>setRating(btn.dataset.rating)));
  $('ratingForm').addEventListener('submit',saveRatingForm);

  if(window.matchMedia){
    const media=window.matchMedia('(prefers-color-scheme: dark)');
    const syncSystemTheme=()=>{ if(settings.theme==='system') applyPreferredTheme(); };
    if(media.addEventListener) media.addEventListener('change',syncSystemTheme);
  }

  document.querySelectorAll('.modal').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('show')}));
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){
      closeNotificationPanel();
      document.querySelectorAll('.modal.show').forEach(m=>m.classList.remove('show'));
      if(document.querySelector('.settings-subview.active') && location.hash==='#pengaturan') openSettingsView('menu');
    }
  });

  window.app={
    detail:detailGuest,
    edit:editGuest,
    checkout:checkoutGuest,
    remove:removeGuest,
    editTeam:editTeamMember,
    deleteTeam:deleteTeamMember,
    editDepartment,
    deleteDepartment
  };

  async function pollState(){
    if(document.hidden) return;
    const mutationsBefore=localMutationCount;
    let data;
    try{ data=await api('GET','/api/state'); }
    catch{ return; }
    if(mutationsBefore!==localMutationCount) return;
    applyRemoteState(data);
  }

  function applyRemoteState(data){
    if(Array.isArray(data.guests)) guests=data.guests;
    if(Array.isArray(data.departments)) departments=data.departments;
    if(Array.isArray(data.team) && !$('teamModal').classList.contains('show')) team=data.team;
    if(data.actor) actor=data.actor;
    if(data.subscription) subscription=CATAMU_FLOW.normalizeSubscription({...subscriptionDefaults,...data.subscription});
    if(Array.isArray(data.notifications)){
      data.notifications.filter(n=>!n.read && !seenNotificationIds.has(n.id)).forEach(showBrowserNotification);
      data.notifications.forEach(n=>seenNotificationIds.add(n.id));
      notifications=data.notifications;
    }
    renderNotifications();
    if($('page-dashboard').classList.contains('active')) renderDashboard();
    if($('page-guests').classList.contains('active')) renderGuests();
    if($('settingsSubscriptionView').classList.contains('active')) renderSubscription();
    renderSettingsMenuNotes();
    applyAccessControl(true);
  }

  applySettings();
  applyPreferredTheme();
  updateClock();
  syncClockTicker();
  document.addEventListener('visibilitychange',syncClockTicker);
  renderNotifications();
  renderPwaInstallState();
  registerPwaServiceWorker();
  routeFromHash();
  applyAccessControl(true);
  if(BOOT.toast) toast(BOOT.toast);
  setInterval(pollState,POLL_INTERVAL);
  document.addEventListener('visibilitychange',()=>{ if(!document.hidden) pollState(); });
})();
