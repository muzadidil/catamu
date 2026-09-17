@php
  $office = $settings['office'];
  $initials = collect(preg_split('/\s+/', trim($office)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('') ?: 'CA';
  $shortUrl = preg_replace('#^https?://#', '', $url);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Poster QR Cek-in • {{ $office }}</title>
  <link rel="icon" type="image/png" href="{{ asset('icon-192.png') }}" />
  <style>
    @page{size:A4;margin:0}
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;background:#e5e7eb;color:#0f172a;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .toolbar{position:sticky;top:0;z-index:2;display:flex;justify-content:center;gap:10px;padding:14px;background:#fff;border-bottom:1px solid #e2e8f0}
    .toolbar button,.toolbar a{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 18px;border-radius:12px;border:1px solid #d0d5dd;background:#fff;color:#0f172a;font:inherit;font-size:14px;font-weight:700;text-decoration:none;cursor:pointer}
    .toolbar button{background:#b91c1c;border-color:#b91c1c;color:#fff}
    .sheet{width:210mm;min-height:297mm;margin:24px auto;background:#fff;box-shadow:0 20px 60px -20px rgba(15,23,42,.35);display:flex;flex-direction:column;overflow:hidden}
    .top{padding:22mm 18mm 34mm;text-align:center;color:#fff;background:radial-gradient(70% 120% at 100% 0%,rgba(248,113,113,.5),transparent 60%),linear-gradient(135deg,#b91c1c,#7f1d1d)}
    .logo{width:22mm;height:22mm;margin:0 auto 8mm;border-radius:6mm;background:#fff;color:#b91c1c;display:grid;place-items:center;font-size:9mm;font-weight:900}
    .welcome{margin:0;font-size:5mm;letter-spacing:.08em;text-transform:uppercase;opacity:.85}
    h1{margin:3mm 0 0;font-size:12mm;line-height:1.1;letter-spacing:-.02em}
    .body{flex:1;margin-top:-20mm;padding:0 18mm 16mm;display:flex;flex-direction:column;align-items:center;text-align:center}
    .qr-card{padding:9mm;border-radius:10mm;background:#fff;box-shadow:0 12px 40px -12px rgba(15,23,42,.35);border:1px solid #e2e8f0}
    .qr-card svg{display:block;width:92mm;height:92mm;shape-rendering:crispEdges}
    .qr-card svg .dark{fill:#0f172a}
    .scan{margin:10mm 0 0;font-size:9mm;font-weight:900;letter-spacing:-.02em}
    .scan-sub{margin:3mm 0 0;font-size:4.6mm;color:#475569}
    .url{margin-top:7mm;padding:3mm 7mm;border-radius:99mm;background:#f1f5f9;font-size:4.4mm;font-weight:700;color:#0f172a;word-break:break-all}
    .steps{display:grid;grid-template-columns:repeat(3,1fr);gap:6mm;width:100%;margin-top:12mm}
    .step{padding:6mm 4mm;border-radius:6mm;background:#fef2f2;text-align:center}
    .step b{display:grid;place-items:center;width:10mm;height:10mm;margin:0 auto 3mm;border-radius:50%;background:#b91c1c;color:#fff;font-size:4.6mm}
    .step span{font-size:4mm;font-weight:700;color:#334155;line-height:1.35}
    .foot{padding:6mm 18mm;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;font-size:3.6mm;color:#64748b}
    .foot strong{color:#b91c1c}
    @media print{body{background:#fff}.toolbar{display:none}.sheet{margin:0;box-shadow:none}}
    @media screen and (max-width:820px){.sheet{transform-origin:top center;transform:scale(.46);margin:10px auto -150mm}}
  </style>
</head>
<body>
  <div class="toolbar">
    <button type="button" onclick="window.print()">Cetak Poster</button>
    <a href="{{ $url }}" target="_blank" rel="noopener">Buka Halaman Cek-in</a>
  </div>
  <div class="sheet">
    <div class="top">
      <div class="logo">{{ $initials }}</div>
      <p class="welcome">Selamat Datang di</p>
      <h1>{{ $office }}</h1>
    </div>
    <div class="body">
      <div class="qr-card">{!! $qrSvg !!}</div>
      <p class="scan">Scan untuk Cek-in</p>
      <p class="scan-sub">Isi data kunjungan Anda langsung dari HP — tanpa instal aplikasi.</p>
      <div class="url">{{ $shortUrl }}</div>
      <div class="steps">
        <div class="step"><b>1</b><span>Buka kamera HP &amp; arahkan ke QR</span></div>
        <div class="step"><b>2</b><span>Isi identitas &amp; tujuan kunjungan</span></div>
        <div class="step"><b>3</b><span>Tunggu, resepsionis segera menghubungi</span></div>
      </div>
    </div>
    <div class="foot"><span>Terima kasih atas kunjungan Anda</span><span>Didukung <strong>CATAMU</strong></span></div>
  </div>
</body>
</html>
