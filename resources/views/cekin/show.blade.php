@extends('layouts.catamu')

@php
  $office = $settings['office'];
  $initials = collect(preg_split('/\s+/', trim($office)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') ?: 'CA';
  $visible = fn ($key) => $fields[$key]['visible'] ?? true;
  $required = fn ($key) => ($fields[$key]['visible'] ?? true) && ($fields[$key]['required'] ?? false);
  $star = fn ($key) => $required($key) ? '<span class="required">*</span>' : '<span class="ck-optional">opsional</span>';
@endphp

@section('title', 'Cek-in Tamu • '.$office)
@section('favicon', \App\Support\TenantBranding::iconUrl($tenant) ?? '')
@section('app-name', $office)
@push('meta')
  <meta name="description" content="Halaman cek-in tamu {{ $office }}. Isi data kunjungan Anda di sini." />
  <meta name="robots" content="noindex" />
@endpush
@push('styles')
  <link rel="stylesheet" href="{{ asset('css/cekin.css') }}?v={{ filemtime(public_path('css/cekin.css')) }}" />
@endpush

@section('body')
<div class="ck-page">
  <header class="ck-hero">
    <div class="ck-hero-inner">
      <div class="ck-office">
        <span class="ck-office-logo">{{ $initials }}</span>
        <div>
          <p class="ck-office-label">Selamat datang di</p>
          <h1>{{ $office }}</h1>
        </div>
      </div>
      <div class="ck-office-meta">
        @if ($settings['address'] && $settings['address'] !== 'Alamat kantor belum diatur')
        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $settings['address'] }}</span>
        @endif
        <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $settings['hours'] }}</span>
        <span class="ck-clock" id="ckClock">--:--</span>
      </div>
    </div>
  </header>

  <main class="ck-main">
    @unless ($available)
    <section class="ck-card ck-state">
      <span class="ck-state-icon warn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5M12 18h.01"/></svg></span>
      <h2>Cek-in mandiri sedang tidak tersedia</h2>
      <p>Silakan lapor langsung ke meja resepsionis {{ $office }}. Terima kasih atas pengertian Anda.</p>
    </section>
    @else
    <section class="ck-card" id="ckFormCard">
      <div class="ck-card-head">
        <div>
          <h2>Cek-in Tamu</h2>
          <p>Isi data kunjungan Anda. Resepsionis akan menerima pemberitahuan setelah Anda mengirim.</p>
        </div>
        <ol class="ck-steps" aria-hidden="true">
          <li class="is-active"><span>1</span>Identitas</li>
          <li><span>2</span>Tujuan</li>
          @if ($visible('photo') || $visible('signature'))
          <li><span>3</span>Verifikasi</li>
          @endif
        </ol>
      </div>

      <form id="ckForm" novalidate>
        <input type="text" name="website" class="ck-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true" />

        <fieldset class="ck-section" data-step="1">
          <legend><span class="ck-section-num">1</span>Identitas Anda</legend>
          <div class="ck-grid">
            <div class="ck-field ck-full">
              <label for="ckName">Nama Lengkap <span class="required">*</span></label>
              <input class="field" id="ckName" name="name" required autocomplete="name" placeholder="Masukkan nama lengkap" />
            </div>
            @if ($visible('phone'))
            <div class="ck-field">
              <label for="ckPhone">Nomor HP / WhatsApp {!! $star('phone') !!}</label>
              <input class="field" id="ckPhone" name="phone" inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" @required($required('phone')) />
            </div>
            @endif
            @if ($visible('company'))
            <div class="ck-field">
              <label for="ckCompany">Instansi / Perusahaan {!! $star('company') !!}</label>
              <input class="field" id="ckCompany" name="company" autocomplete="organization" placeholder="Nama instansi atau perusahaan" @required($required('company')) />
            </div>
            @endif
            @if ($visible('email'))
            <div class="ck-field">
              <label for="ckEmail">Email {!! $star('email') !!}</label>
              <input class="field" id="ckEmail" name="email" type="email" inputmode="email" autocomplete="email" placeholder="nama@email.com" @required($required('email')) />
            </div>
            @endif
            @if ($visible('vehicle'))
            <div class="ck-field">
              <label for="ckVehicle">Plat Nomor Kendaraan {!! $star('vehicle') !!}</label>
              <input class="field" id="ckVehicle" name="vehicle" autocapitalize="characters" placeholder="Contoh: N 1234 AB" @required($required('vehicle')) />
            </div>
            @endif
          </div>
        </fieldset>

        <fieldset class="ck-section" data-step="2">
          <legend><span class="ck-section-num">2</span>Tujuan Kunjungan</legend>
          <div class="ck-grid">
            @if ($visible('meet'))
            <div class="ck-field ck-full">
              <label for="ckMeet">Orang / Bagian yang Ditemui {!! $star('meet') !!}</label>
              <select class="field" id="ckMeet" name="departmentId" @required($required('meet'))>
                <option value="">Pilih bagian yang dituju</option>
                @foreach ($departments as $department)
                <option value="{{ $department->id }}">{{ $department->name }}{{ $department->code ? ' ('.$department->code.')' : '' }}</option>
                @endforeach
                <option value="__manual__">Lainnya / tulis nama orang</option>
              </select>
              <input class="field ck-manual" id="ckMeetManual" name="meet" placeholder="Nama orang atau bagian yang ditemui" hidden />
            </div>
            @endif
            @if ($visible('purpose'))
            <div class="ck-field ck-full">
              <label for="ckPurpose">Keperluan Kunjungan {!! $star('purpose') !!}</label>
              <textarea class="field" id="ckPurpose" name="purpose" rows="3" placeholder="Contoh: rapat, antar dokumen, wawancara" @required($required('purpose'))></textarea>
            </div>
            @endif
            @if ($visible('people'))
            <div class="ck-field">
              <label for="ckPeople">Jumlah Pengunjung {!! $star('people') !!}</label>
              <div class="ck-stepper">
                <button type="button" data-step-people="-1" aria-label="Kurangi">−</button>
                <input class="field" id="ckPeople" name="people" type="number" min="1" max="100" value="{{ max(1, (int) $settings['defaultPeople']) }}" inputmode="numeric" />
                <button type="button" data-step-people="1" aria-label="Tambah">+</button>
              </div>
            </div>
            @endif
            @if ($visible('notes'))
            <div class="ck-field">
              <label for="ckNotes">Catatan {!! $star('notes') !!}</label>
              <input class="field" id="ckNotes" name="notes" placeholder="Catatan tambahan" @required($required('notes')) />
            </div>
            @endif
          </div>
        </fieldset>

        @if ($visible('photo') || $visible('signature'))
        <fieldset class="ck-section" data-step="3">
          <legend><span class="ck-section-num">3</span>Foto &amp; Tanda Tangan</legend>
          <div class="ck-media">
            @if ($visible('photo'))
            <div class="ck-media-item" data-required="{{ $required('photo') ? '1' : '0' }}">
              <p class="ck-media-label">Foto Diri {!! $star('photo') !!}</p>
              <div class="camera-stage" id="ckCameraStage">
                <video id="ckVideo" autoplay muted playsinline></video>
                <img id="ckPhotoPreview" alt="Foto tamu" />
                <div class="camera-placeholder"><strong>Belum ada foto</strong>Nyalakan kamera atau pilih foto dari galeri.</div>
              </div>
              <div class="ck-media-actions">
                <button class="btn" type="button" id="ckCameraBtn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h4l1.5-2h5L16 7h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="4"/></svg><span>Buka Kamera</span></button>
                <button class="btn btn-primary" type="button" id="ckCaptureBtn" hidden><span>Ambil Foto</span></button>
                <button class="btn" type="button" id="ckChooseBtn"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="2"/><path d="m4 18 5-5 4 4 2-2 5 4"/></svg><span>Pilih Foto</span></button>
                <button class="btn btn-ghost" type="button" id="ckRetakeBtn" hidden><span>Ulangi</span></button>
              </div>
              <input type="file" id="ckPhotoFile" accept="image/*" hidden />
            </div>
            @endif
            @if ($visible('signature'))
            <div class="ck-media-item" data-required="{{ $required('signature') ? '1' : '0' }}">
              <p class="ck-media-label">Tanda Tangan {!! $star('signature') !!}</p>
              <div class="signature-pad ck-signature" id="ckSignaturePad">
                <canvas id="ckSignature"></canvas>
                <div class="signature-hint">Tanda tangan di area ini dengan jari atau mouse</div>
              </div>
              <div class="ck-media-actions">
                <button class="btn btn-ghost" type="button" id="ckClearSignature"><span>Hapus Tanda Tangan</span></button>
              </div>
            </div>
            @endif
          </div>
        </fieldset>
        @endif

        <label class="ck-consent">
          <input type="checkbox" id="ckConsent" required />
          <span>Saya setuju data kunjungan ini dicatat oleh <strong>{{ $office }}</strong> sesuai <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Kebijakan Privasi</a>.</span>
        </label>

        <div class="ck-submit-bar">
          <button class="btn btn-primary ck-submit" type="submit" id="ckSubmit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
            <span>Kirim Cek-in</span>
          </button>
        </div>
      </form>
    </section>

    <section class="ck-card ck-state ck-success" id="ckSuccess" hidden>
      <span class="ck-state-icon ok"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg></span>
      <h2>Terima kasih, <span id="ckSuccessName">Tamu</span>!</h2>
      <p>Kedatangan Anda sudah tercatat. Silakan menunggu, {{ $settings['officer'] }} {{ $office }} akan segera menghubungi Anda.</p>
      <div class="ck-success-time" id="ckSuccessTime"></div>
      <button class="btn btn-primary" type="button" id="ckAgain">Cek-in Tamu Lain</button>
      <p class="ck-countdown" id="ckCountdown"></p>
    </section>
    @endunless
  </main>

  <footer class="ck-footer">
    <a href="{{ route('landing') }}" target="_blank" rel="noopener"><span class="ck-footer-mark">CA</span>Didukung CATAMU</a>
    <span>•</span>
    <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Kebijakan Privasi</a>
  </footer>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
@endsection

@push('scripts')
<script>
  window.CATAMU_CEKIN = {{ Js::from([
      'submitUrl' => route('cekin.store', ['slug' => $tenant->slug]),
      'defaultPeople' => max(1, (int) $settings['defaultPeople']),
      'timeFormat' => $settings['timeFormat'],
  ]) }};
</script>
<script src="{{ asset('js/cekin.js') }}?v={{ filemtime(public_path('js/cekin.js')) }}"></script>
@endpush
