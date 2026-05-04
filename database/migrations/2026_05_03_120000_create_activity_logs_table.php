<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 50)->default('system');
            $table->string('event', 120);
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->ipAddress('ip_address')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
