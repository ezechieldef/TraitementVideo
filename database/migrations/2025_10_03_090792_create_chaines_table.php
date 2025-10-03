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
        Schema::create('chaines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entite_id')->constrained('entites')->onDelete('cascade');
            $table->string('titre');
            $table->string('channel_id')->nullable();
            $table->text('youtube_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chaines');
    }
};
