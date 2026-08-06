<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('base_model_image')->nullable()->after('face_image');
            $table->string('base_model_fingerprint')->nullable()->after('base_model_image');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['base_model_image', 'base_model_fingerprint']);
        });
    }
};
