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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->integer('ordre')->default(0);
            $table->string('titre')->nullable();
            $table->integer('debut'); // en secondes
            $table->integer('fin'); // en secondes
            $table->integer('longueur')->nullable();
            $table->longText('transcription')->nullable();
            $table->text('custom_instruction')->nullable();
            $table->boolean('isFromCron')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
