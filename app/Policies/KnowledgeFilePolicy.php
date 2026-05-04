<?php

namespace App\Policies;

use App\Models\KnowledgeFile;
use App\Models\User;

class KnowledgeFilePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, KnowledgeFile $knowledgeFile): bool
    {
        return $user->agent_id === $knowledgeFile->agent_id;
    }

    public function update(User $user, KnowledgeFile $knowledgeFile): bool
    {
        return $user->agent_id === $knowledgeFile->agent_id;
    }

    public function delete(User $user, KnowledgeFile $knowledgeFile): bool
    {
        return $user->agent_id === $knowledgeFile->agent_id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->agent_id !== null;
    }
}
