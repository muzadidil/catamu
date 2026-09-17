@php
  $name = $tenant->officeName();
  $plan = config('catamu.plans')[0];
@endphp
<div class="ad-actions {{ $class ?? '' }}">
  @unless ($hideDetail ?? false)
  <a class="ad-btn ad-btn--pill ad-btn--neutral" href="{{ route('admin.tenants.show', $tenant) }}">@include('admin.partials.icon', ['name' => 'eye'])<span>Detail</span></a>
  @endunless

  <form method="POST" action="{{ route('admin.tenants.yearly', $tenant) }}"
        data-confirm data-confirm-title="Aktifkan {{ $plan['name'] }}?"
        data-confirm-message="Masa aktif {{ $name }} bertambah {{ $plan['days'] }} hari tanpa pembayaran. Jika masih aktif, hari tambahan dihitung dari tanggal kedaluwarsa saat ini."
        data-confirm-label="Aktifkan" data-confirm-variant="accent">
    @csrf
    <button class="ad-btn ad-btn--pill ad-btn--accent-outline" type="submit">@include('admin.partials.icon', ['name' => 'calendar'])<span>Aktifkan {{ $plan['name'] }}</span></button>
  </form>

  <form method="POST" action="{{ route('admin.tenants.lifetime', $tenant) }}"
        data-confirm
        data-confirm-title="{{ $tenant->lifetime ? 'Batalkan paket Lifetime?' : 'Jadikan Lifetime?' }}"
        data-confirm-message="{{ $tenant->lifetime ? $name.' kembali mengikuti masa aktif langganan biasa.' : $name.' mendapat akses penuh tanpa batas waktu.' }}"
        data-confirm-label="{{ $tenant->lifetime ? 'Batalkan Lifetime' : 'Jadikan Lifetime' }}" data-confirm-variant="accent">
    @csrf
    <button class="ad-btn ad-btn--pill {{ $tenant->lifetime ? 'ad-btn--accent-outline' : 'ad-btn--accent' }}" type="submit">@include('admin.partials.icon', ['name' => 'infinity'])<span>{{ $tenant->lifetime ? 'Batalkan Lifetime' : 'Jadikan Lifetime' }}</span></button>
  </form>

  <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}"
        data-confirm data-confirm-title="Hapus {{ $name }}?"
        data-confirm-message="Seluruh data kantor ini — tamu, foto, tim, dan riwayat pembayaran — akan dihapus permanen dan tidak dapat dikembalikan."
        data-confirm-label="Hapus Permanen" data-confirm-variant="danger" data-confirm-type="HAPUS">
    @csrf
    @method('DELETE')
    <input type="hidden" name="confirmation" value="" data-confirm-type-target />
    <button class="ad-btn ad-btn--pill ad-btn--danger-soft" type="submit">@include('admin.partials.icon', ['name' => 'trash'])<span>Hapus</span></button>
  </form>
</div>
