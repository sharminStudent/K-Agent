<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->string('severity', 20)->default('normal')->after('category');
            $table->string('status', 20)->default('success')->after('severity');

            $table->index(['severity', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        DB::table('activity_logs')
            ->whereNull('severity')
            ->update(['severity' => 'normal']);

        DB::table('activity_logs')
            ->whereNull('status')
            ->update(['status' => 'success']);
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropIndex(['severity', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['severity', 'status']);
        });
    }
};
