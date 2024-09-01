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
        Schema::create('access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token', '36')->unique();
            $table->boolean('banned');
            $table->integer('rate_limit')->default(50); // Limit the number of requests for this token per minute.
            $table->unsignedBigInteger('lifetime',)->default('31556952000'); // default lifecycle of a token is one year. (Value in milliseconds)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_tokens');
    }
};
