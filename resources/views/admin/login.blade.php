<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#b91c1c" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="robots" content="noindex" />
  <title>Masuk • Backoffice CATAMU</title>
  <link rel="icon" type="image/png" href="{{ asset('icon-192.png') }}" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" />
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}" />
</head>
<body class="ad-auth-body">
<main class="ad-auth">
  <div class="ad-auth-card">
    <span class="ad-brand ad-auth-brand">
      <span class="ad-brand-mark">CA</span>
      <span class="ad-brand-text"><b>CATAMU</b><small>Backoffice</small></span>
    </span>

    <h1>Masuk Super Admin</h1>
    <p class="ad-auth-lead">Halaman pengelola platform: verifikasi pembayaran, langganan kantor, afiliasi, dan pengaturan.</p>

    @if (session('error') || $errors->any())
    <p class="ad-auth-error">
      @include('admin.partials.icon', ['name' => 'alert'])
      <span>{{ session('error') ?? $errors->first() }}</span>
    </p>
    @endif

    @if ($googleConfigured)
    <a class="ad-btn ad-btn--primary ad-btn--block" href="{{ route('google.redirect') }}">Masuk dengan Google</a>
    <p class="ad-auth-note">Pakai akun Google yang terdaftar di <code>SUPER_ADMIN_EMAILS</code>.</p>
    @else
    <form class="ad-auth-form" method="POST" action="{{ route('admin.login.submit') }}">
      @csrf
      <label class="ad-auth-field">
        <span>Email</span>
        <input name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@gmail.com" required autofocus />
      </label>
      <label class="ad-auth-field">
        <span>Password</span>
        <input name="password" type="password" autocomplete="current-password" placeholder="Password super admin" required />
      </label>
      <button class="ad-btn ad-btn--primary ad-btn--block" type="submit">Masuk</button>
    </form>
    <p class="ad-auth-note">Jalur sementara selama login Google belum diaktifkan.</p>
    @endif

    <a class="ad-auth-back" href="{{ route('landing') }}">Kembali ke {{ config('catamu.domains.main') }}</a>
  </div>
</main>
</body>
</html>
