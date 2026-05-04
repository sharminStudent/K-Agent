<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(
        string $event,
        string $description,
        string $category = 'system',
        ?Agent $agent = null,
        ?User $user = null,
        ?Model $subject = null,
        array $meta = [],
        ?string $ipAddress = null,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'agent_id' => $agent?->id ?? $user?->agent_id,
            'user_id' => $user?->id,
            'category' => $category,
            'event' => $event,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $ipAddress ?? request()->ip(),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
