<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $guarded = ['id'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toClient(): array
    {
        return [
            'id' => (string) $this->id,
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
