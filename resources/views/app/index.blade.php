@extends('layouts.catamu')

@section('body-class', $user->theme === 'dark' ? 'dark' : '')
@section('app-url', $state['links']['appUrl'])
@section('manifest', route('app.manifest', ['slug' => $tenant->slug]))

@section('body')
<div class="app">
  @include('app.partials.sidebar')

  <div class="mobile-overlay" id="overlay"></div>

  <main class="main">
    @include('app.partials.topbar')
    <div class="content">
      @include('app.pages.dashboard')

      @include('app.pages.guests')

      @include('app.pages.form')


      <section class="page" id="page-settings">
        <div class="settings-shell">
          @include('app.settings.menu')

          @include('app.settings.subscription')

          @include('app.settings.account')

          @include('app.settings.team')

          @include('app.settings.departments')

          @include('app.settings.guest-fields')

          @include('app.settings.application')

          @include('app.settings.notifications')

          @include('app.settings.install')

          @include('app.settings.contact')

          @include('app.settings.feedback')

          @include('app.settings.rating')

          @include('app.settings.about')
        </div>
      </section>
    </div>
  </main>
</div>

@include('app.partials.bottom-nav')

@include('app.modals.team')

@include('app.modals.detail')

@include('app.modals.checkout')


@include('app.modals.qris')


@include('app.modals.delete-account')

<div class="toast" id="toast"></div>

<script>window.CATAMU_STATE = {{ Js::from($state + ['toast' => session('toast')]) }};</script>
<script src="{{ asset('js/catamu.js') }}?v={{ filemtime(public_path('js/catamu.js')) }}"></script>
@endsection
