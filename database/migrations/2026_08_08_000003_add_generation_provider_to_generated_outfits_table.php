<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_outfits', function (Blueprint $table) {
            if (! Schema::hasColumn('generated_outfits', 'generation_provider')) {
                $table->string('generation_provider', 32)->nullable()->after('status');
            }

            if (! Schema::hasColumn('generated_outfits', 'generation_model')) {
                $table->string('generation_model', 64)->nullable()->after('generation_provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('generated_outfits', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('generated_outfits', 'generation_provider')) {
                $columns[] = 'generation_provider';
            }

            if (Schema::hasColumn('generated_outfits', 'generation_model')) {
                $columns[] = 'generation_model';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
