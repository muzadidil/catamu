<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'people' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function mediaUrl(string $kind): string
    {
        $path = $kind === 'photo' ? $this->photo_path : $this->signature_path;
        if (! $path) {
            return '';
        }

        return route('media.guest', ['guest' => $this->id, 'kind' => $kind, 'v' => substr(md5($path), 0, 8)]);
    }

    public function toClient(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'phone' => $this->phone ?? '',
            'company' => $this->company ?? '',
            'email' => $this->email ?? '',
            'vehicle' => $this->vehicle ?? '',
            'meet' => $this->meet ?? '',
            'departmentId' => $this->department_id ? (string) $this->department_id : '',
            'purpose' => $this->purpose ?? '',
            'people' => $this->people ?: 1,
            'notes' => $this->notes ?? '',
            'photo' => $this->mediaUrl('photo'),
            'signature' => $this->mediaUrl('signature'),
            'checkIn' => $this->check_in?->toISOString(),
            'checkOut' => $this->check_out?->toISOString(),
            'checkoutNote' => $this->checkout_note ?? '',
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
