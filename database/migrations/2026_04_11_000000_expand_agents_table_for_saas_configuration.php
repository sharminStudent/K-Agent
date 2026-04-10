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
        Schema::table('agents', function (Blueprint $table): void {
            $table->string('slug')->unique()->nullable()->after('company_name');
            $table->string('website_url')->nullable()->after('slug');
            $table->string('industry')->nullable()->after('website_url');
            $table->text('company_description')->nullable()->after('industry');
            $table->string('support_email')->nullable()->after('contact_email');
            $table->string('support_phone')->nullable()->after('support_email');
            $table->text('welcome_message')->nullable()->after('system_prompt');
            $table->text('fallback_message')->nullable()->after('welcome_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table): void {
            $table->dropColumn([
                'slug',
                'website_url',
                'industry',
                'company_description',
                'support_email',
                'support_phone',
                'welcome_message',
                'fallback_message',
            ]);
        });
    }
};
