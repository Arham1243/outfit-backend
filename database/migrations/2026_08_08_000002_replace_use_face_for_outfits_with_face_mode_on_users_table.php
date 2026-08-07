<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'face_mode')) {
                $table->string('face_mode', 32)->default('ai_model')->after('height');
            }
        });

        if (Schema::hasColumn('users', 'use_face_for_outfits')) {
            DB::table('users')
                ->where('use_face_for_outfits', true)
                ->update(['face_mode' => 'user_face']);
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'use_face_for_outfits')) {
                $table->dropColumn('use_face_for_outfits');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'use_face_for_outfits')) {
                $table->boolean('use_face_for_outfits')->default(false)->after('height');
            }
        });

        if (Schema::hasColumn('users', 'face_mode')) {
            DB::table('users')
                ->where('face_mode', 'user_face')
                ->update(['use_face_for_outfits' => true]);
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'face_mode')) {
                $table->dropColumn('face_mode');
            }
        });
    }
};
