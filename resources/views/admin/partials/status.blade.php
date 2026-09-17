<span class="ad-badge ad-badge--{{ \App\Support\Format::stateTone($tenant) }}">{{ $tenant->subscriptionLabel() }}</span>
@if ($tenant->pendingPayment && ! str_contains($tenant->subscriptionLabel(), 'Pending') && $tenant->subscriptionLabel() !== 'Menunggu Verifikasi')
<span class="ad-badge ad-badge--warning ad-badge--outline">Bukti bayar masuk</span>
@endif
