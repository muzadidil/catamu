<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function toClient(): array
    {
        return [
            'id' => (string) $this->id,
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'guestId' => $this->guest_id ? (string) $this->guest_id : '',
            'department' => $this->department ?? '',
            'createdAt' => $this->created_at?->toISOString(),
            'read' => $this->read_at !== null,
            'stored' => $this->exists,
        ];
    }
}
