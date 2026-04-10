<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'public_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'status',
        'last_message_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChatSession $chatSession): void {
            if (blank($chatSession->public_id)) {
                $chatSession->public_id = (string) Str::ulid();
            }
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
