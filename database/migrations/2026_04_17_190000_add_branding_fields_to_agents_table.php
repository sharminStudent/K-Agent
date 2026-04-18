<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table): void {
            $table->string('login_logo_path')->nullable()->after('logo_path');
            $table->string('light_logo_path')->nullable()->after('login_logo_path');
            $table->string('dark_logo_path')->nullable()->after('light_logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table): void {
            $table->dropColumn([
                'login_logo_path',
                'light_logo_path',
                'dark_logo_path',
            ]);
        });
    }
};
