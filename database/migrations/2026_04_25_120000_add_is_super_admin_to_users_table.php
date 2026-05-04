<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_super_admin')->default(false)->after('agent_id');
        });

        $existingUser = DB::table('users')
            ->where('email', 'super@agent.com')
            ->first();

        if ($existingUser) {
            DB::table('users')
                ->where('id', $existingUser->id)
                ->update([
                    'name' => 'Super Admin',
                    'password' => Hash::make('super@agent'),
                    'email_verified_at' => now(),
                    'agent_id' => null,
                    'is_super_admin' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'super@agent.com',
            'email_verified_at' => now(),
            'password' => Hash::make('super@agent'),
            'agent_id' => null,
            'is_super_admin' => true,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_super_admin');
        });
    }
};
