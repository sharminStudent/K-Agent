<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory;

    public const PAYMENT_STATUS_TRIAL = 'trial';

    public const PAYMENT_STATUS_ACTIVE = 'active';

    public const PAYMENT_STATUS_PAST_DUE = 'past_due';

    public const PAYMENT_STATUS_CANCELED = 'canceled';

    public const PAYMENT_STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'company_name',
        'slug',
        'website_url',
        'industry',
        'company_description',
        'logo_path',
        'login_logo_path',
        'light_logo_path',
        'dark_logo_path',
        'widget_token',
        'contact_email',
        'support_email',
        'support_phone',
        'system_prompt',
        'welcome_message',
        'fallback_message',
        'settings',
        'is_active',
        'subscription_plan',
        'payment_status',
        'chat_limit',
        'lead_limit',
        'monthly_token_limit',
        'monthly_chat_count',
        'monthly_lead_count',
        'monthly_token_count',
        'api_request_count',
        'last_api_request_at',
        'last_error_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'chat_limit' => 'integer',
            'lead_limit' => 'integer',
            'monthly_token_limit' => 'integer',
            'monthly_chat_count' => 'integer',
            'monthly_lead_count' => 'integer',
            'monthly_token_count' => 'integer',
            'api_request_count' => 'integer',
            'last_api_request_at' => 'datetime',
            'last_error_at' => 'datetime',
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function primaryUser(): HasOne
    {
        return $this->hasOne(User::class)->oldestOfMany();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function paymentRecords(): HasMany
    {
        return $this->hasMany(PaymentRecord::class);
    }

    public function normalizedPaymentStatus(): ?string
    {
        $status = strtolower(trim((string) $this->payment_status));

        return $status !== '' ? $status : null;
    }

    public function allowsWorkspaceAccess(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return in_array($this->normalizedPaymentStatus(), [
            null,
            self::PAYMENT_STATUS_TRIAL,
            self::PAYMENT_STATUS_ACTIVE,
        ], true);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function getLoginLogoUrlAttribute(): ?string
    {
        if (blank($this->login_logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->login_logo_path);
    }

    public function getLightLogoUrlAttribute(): ?string
    {
        if (blank($this->light_logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->light_logo_path);
    }

    public function getDarkLogoUrlAttribute(): ?string
    {
        if (blank($this->dark_logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->dark_logo_path);
    }
}
