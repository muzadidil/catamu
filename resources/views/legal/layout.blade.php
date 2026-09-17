@extends('layouts.catamu')

@section('body')
<div class="legal-page">
  <div class="brand" style="color:var(--text);border-bottom-color:var(--line)">
    <div class="brand-logo" style="background:var(--primary);color:#fff">CA</div>
    <div><h1>CATAMU</h1><small style="color:var(--muted)">@yield('heading')</small></div>
  </div>
  <div class="settings-card">
    <div class="settings-card-head"><div><h3>@yield('heading')</h3><p>Terakhir diperbarui {{ \Illuminate\Support\Carbon::parse('2026-09-17')->translatedFormat('d F Y') }}</p></div></div>
    <div class="settings-card-body">
      @yield('legal')
    </div>
  </div>
</div>
<script>if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) document.body.classList.add('dark');</script>
@endsection
