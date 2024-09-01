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
        Schema::create('access_token_statistic', function (Blueprint $table) {
            $table->id();
            $table->integer('token_id');
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('useragent')->nullable();
            $table->string('device')->nullable();
            $table->string('os')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_token_statistic');
    }
};
