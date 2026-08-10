<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_outfits', function (Blueprint $table) {
            if (! Schema::hasColumn('generated_outfits', 'name')) {
                $table->string('name')->nullable()->after('uuid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('generated_outfits', function (Blueprint $table) {
            if (Schema::hasColumn('generated_outfits', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
