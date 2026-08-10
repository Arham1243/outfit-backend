<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'sidebar_open')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('sidebar_open')->default(true)->after('dark_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'sidebar_open')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('sidebar_open');
            });
        }
    }
};
