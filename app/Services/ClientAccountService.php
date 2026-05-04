<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientAccountService
{
    public function syncPrimaryUser(Agent $agent, array $data): User
    {
        return DB::transaction(function () use ($agent, $data): User {
            $user = $agent->primaryUser()->first();
            $email = trim((string) ($data['contact_email'] ?? ''));

            if ($email === '') {
                throw ValidationException::withMessages([
                    'contact_email' => 'Contact email is required for the client login.',
                ]);
            }

            if ($user === null && blank($data['password'] ?? null)) {
                throw ValidationException::withMessages([
                    'password' => 'Password is required when creating the client login.',
                ]);
            }

            $attributes = [
                'name' => trim((string) ($agent->company_name ?: $agent->name ?: 'Client User')),
                'email' => $email,
                'phone' => $data['support_phone'] ?? null,
                'agent_id' => $agent->id,
                'is_super_admin' => false,
                'is_active' => (bool) ($data['is_active'] ?? $agent->is_active),
            ];

            if (filled($data['password'] ?? null)) {
                $attributes['password'] = (string) $data['password'];
            }

            if ($user) {
                $user->update($attributes);

                return $user->fresh();
            }

            return User::query()->create($attributes);
        });
    }
}
