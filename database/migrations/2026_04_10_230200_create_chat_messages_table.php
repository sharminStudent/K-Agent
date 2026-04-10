<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->ulid('public_id')->unique();
            $table->string('role');
            $table->longText('content');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
            $table->index(['agent_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
