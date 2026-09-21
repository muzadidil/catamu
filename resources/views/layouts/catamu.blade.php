<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="theme-color" content="#b91c1c" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="app-url" content="@yield('app-url', url('/'))" />
  @hasSection('manifest')
  <link rel="manifest" href="@yield('manifest')" />
  @endif
  {{-- Halaman bertenant menimpa ini dengan logo kantor lewat @section('favicon'). --}}
  @php ($faviconUrl = trim($__env->yieldContent('favicon')) ?: asset('icon-192.png'))
  <link rel="icon" type="image/png" href="{{ $faviconUrl }}" />
  <link rel="apple-touch-icon" href="{{ $faviconUrl }}" />
  <meta name="application-name" content="@yield('app-name', 'adatamu.id')" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-title" content="@yield('app-name', 'adatamu.id')" />
  <meta name="apple-mobile-web-app-status-bar-style" content="default" />
  <title>@yield('title', 'adatamu.id')</title>
  @stack('meta')
  <link rel="stylesheet" href="{{ asset('css/catamu.css') }}?v={{ filemtime(public_path('css/catamu.css')) }}" />
  @stack('styles')
</head>
<body class="@yield('body-class')">
@yield('body')
@stack('scripts')
</body>
</html>
