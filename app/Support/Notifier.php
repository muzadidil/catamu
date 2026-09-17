<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\Tenant;
use Illuminate\Support\Str;

class Notifier
{
    /**
     * Port dari addNotification() di JS. Mengembalikan data notifikasi untuk browser,
     * atau null jika jenis kejadian dimatikan di pengaturan notifikasi kantor.
     */
    public static function notify(Tenant $tenant, string $event, string $title, string $message, ?int $guestId = null, ?string $department = null): ?array
    {
        $prefs = $tenant->resolvedNotificationPrefs();
        $enabled = match ($event) {
            'checkin' => $prefs['checkIn'],
            'checkout' => $prefs['checkOut'],
            'update' => $prefs['updates'],
            default => true,
        };
        if (! $enabled) {
            return null;
        }

        $notification = new AppNotification([
            'tenant_id' => $tenant->id,
            'event' => $event,
            'title' => Str::limit($title, 250),
            'message' => $message,
            'guest_id' => $guestId,
            'department' => $department ? Str::limit($department, 145) : null,
        ]);

        if (! $prefs['inApp']) {
            $notification->created_at = now();

            return array_merge($notification->toClient(), ['id' => 'tmp-'.Str::random(10)]);
        }

        $notification->save();
        self::prune($tenant);

        return $notification->toClient();
    }

    private static function prune(Tenant $tenant): void
    {
        $staleIds = AppNotification::where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->skip(config('catamu.notification_limit'))
            ->take(1000)
            ->pluck('id');

        if ($staleIds->isNotEmpty()) {
            AppNotification::whereIn('id', $staleIds)->delete();
        }
    }
}
