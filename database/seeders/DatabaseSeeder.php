<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->isLocal()) {
            User::query()->firstOrCreate(
                ['email' => 'test@example.com'],
                ['name' => 'Test User']
            );
        }

        if (filter_var((string) env('SEED_CLIENT_WORKSPACES', false), FILTER_VALIDATE_BOOL)) {
            $this->call(ClientWorkspaceSeeder::class);
        }
    }
}
