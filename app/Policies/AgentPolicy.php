<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->agent_id === null;
    }

    public function view(User $user, Agent $agent): bool
    {
        return $user->agent_id === $agent->id;
    }

    public function update(User $user, Agent $agent): bool
    {
        return $user->agent_id === $agent->id;
    }

    public function regenerateWidgetToken(User $user, Agent $agent): bool
    {
        return $user->agent_id === $agent->id;
    }

    public function delete(User $user, Agent $agent): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
