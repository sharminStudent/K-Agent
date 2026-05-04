<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BHD');
            $table->string('status')->default('paid');
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
            $table->index(['agent_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
