<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('is_super_admin');
        });

        Schema::table('agents', function (Blueprint $table): void {
            $table->string('subscription_plan')->nullable()->after('is_active');
            $table->string('payment_status')->nullable()->after('subscription_plan');
            $table->unsignedInteger('chat_limit')->nullable()->after('payment_status');
            $table->unsignedInteger('lead_limit')->nullable()->after('chat_limit');
            $table->unsignedBigInteger('monthly_token_limit')->nullable()->after('lead_limit');
            $table->unsignedInteger('monthly_chat_count')->default(0)->after('monthly_token_limit');
            $table->unsignedInteger('monthly_lead_count')->default(0)->after('monthly_chat_count');
            $table->unsignedBigInteger('monthly_token_count')->default(0)->after('monthly_lead_count');
            $table->unsignedInteger('api_request_count')->default(0)->after('monthly_token_count');
            $table->timestamp('last_api_request_at')->nullable()->after('api_request_count');
            $table->timestamp('last_error_at')->nullable()->after('last_api_request_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });

        Schema::table('agents', function (Blueprint $table): void {
            $table->dropColumn([
                'subscription_plan',
                'payment_status',
                'chat_limit',
                'lead_limit',
                'monthly_token_limit',
                'monthly_chat_count',
                'monthly_lead_count',
                'monthly_token_count',
                'api_request_count',
                'last_api_request_at',
                'last_error_at',
            ]);
        });
    }
};
