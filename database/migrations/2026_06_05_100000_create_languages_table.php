<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->char('code', 2);
            $table->char('region_code', 2)->default('');
            $table->string('name');
            $table->boolean('is_rtl')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['code', 'region_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
