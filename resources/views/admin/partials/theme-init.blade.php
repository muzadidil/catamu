{{-- Dipasang di <head> sebelum CSS supaya tema pilihan langsung berlaku tanpa berkedip. Logika tombolnya ada di js/admin.js. --}}
<script>
(function () {
  var theme = null;
  try { theme = localStorage.getItem('adTheme'); } catch (e) {}
  if (theme !== 'light' && theme !== 'dark') {
    theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  document.documentElement.setAttribute('data-theme', theme);
})();
</script>
