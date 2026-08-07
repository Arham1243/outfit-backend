<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'face_image')) {
                $table->string('face_image')->nullable()->after('height');
            }

            if (! Schema::hasColumn('users', 'use_face_for_outfits')) {
                $table->boolean('use_face_for_outfits')->default(false)->after('height');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('users', 'face_image')) {
                $columns[] = 'face_image';
            }

            if (Schema::hasColumn('users', 'use_face_for_outfits')) {
                $columns[] = 'use_face_for_outfits';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
