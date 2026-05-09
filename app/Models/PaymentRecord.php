<?php

namespace App\Models;

use App\Services\SuperAdminNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaymentRecord extends Model
{
    public const STATUS_PAID = 'paid';

    public const STATUS_PENDING = 'pending';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'agent_id',
        'reference',
        'amount',
        'currency',
        'status',
        'billing_period_start',
        'billing_period_end',
        'due_at',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentRecord $record): void {
            if (blank($record->reference)) {
                $record->reference = $record->generateReference();
            }
        });

        static::saved(function (): void {
            app(SuperAdminNotificationService::class)->sync();
        });

        static::deleted(function (): void {
            app(SuperAdminNotificationService::class)->sync();
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function effectiveDueAt(): ?Carbon
    {
        if ($this->due_at instanceof Carbon) {
            return $this->due_at;
        }

        if ($this->billing_period_end instanceof Carbon) {
            return $this->billing_period_end->copy()->endOfDay();
        }

        return null;
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->whereNotNull('due_at')
                ->where('due_at', '<=', now())
                ->orWhere(function (Builder $fallback): void {
                    $fallback
                        ->whereNull('due_at')
                        ->whereNotNull('billing_period_end')
                        ->whereDate('billing_period_end', '<=', now()->toDateString());
                });
        });
    }

    protected function generateReference(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
