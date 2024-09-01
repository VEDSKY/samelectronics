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
        Schema::create('fragments', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('fragment_title', '255')->unique();
            $table->string('fragment_description');
            $table->string('fragment_imaage_url', '255');
            $table->string('fragment_imaage_thumb_url', '255');
            $table->string('fragment_imaage_low_thumb_url', '255');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fragments');
    }
};
