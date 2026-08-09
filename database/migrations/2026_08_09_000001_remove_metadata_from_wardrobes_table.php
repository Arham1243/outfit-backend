<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wardrobes', function (Blueprint $table) {
            if (Schema::hasColumn('wardrobes', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wardrobes', function (Blueprint $table) {
            if (! Schema::hasColumn('wardrobes', 'metadata')) {
                $table->json('metadata')->nullable()->after('type');
            }
        });
    }
};
