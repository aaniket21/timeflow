<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('slug', 60)->unique();
            $table->string('name', 80);
            $table->string('description', 200);
            $table->string('icon', 10);
            $table->enum('category', ['consistency', 'volume', 'focus', 'explorer']);
            $table->unsignedSmallInteger('xp_reward')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
