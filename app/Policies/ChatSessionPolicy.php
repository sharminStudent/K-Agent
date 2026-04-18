<?php

namespace App\Policies;

use App\Models\ChatSession;
use App\Models\User;

class ChatSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ChatSession $chatSession): bool
    {
        return $user->agent_id === $chatSession->agent_id;
    }

    public function update(User $user, ChatSession $chatSession): bool
    {
        return false;
    }

    public function delete(User $user, ChatSession $chatSession): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
