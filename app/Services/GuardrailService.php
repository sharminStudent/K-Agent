<?php

namespace App\Services;

use App\Models\Agent;

class GuardrailService
{
    /**
     * @param  array<int, array<string, mixed>>  $contextChunks
     */
    public function shouldUseFallback(array $contextChunks): bool
    {
        return $contextChunks === [];
    }

    public function fallbackMessage(Agent $agent): string
    {
        return $agent->fallback_message ?: 'I do not have enough company knowledge to answer that yet.';
    }
}
