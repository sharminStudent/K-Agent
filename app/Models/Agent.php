<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'slug',
        'website_url',
        'industry',
        'company_description',
        'widget_token',
        'contact_email',
        'support_email',
        'support_phone',
        'system_prompt',
        'welcome_message',
        'fallback_message',
        'settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Agent $agent): void {
            if (blank($agent->slug)) {
                $agent->slug = Str::slug($agent->company_name ?: $agent->name).'-'.Str::lower(Str::random(6));
            }

            if (blank($agent->widget_token)) {
                $agent->widget_token = Str::random(40);
            }
        });
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function knowledgeFiles(): HasMany
    {
        return $this->hasMany(KnowledgeFile::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
