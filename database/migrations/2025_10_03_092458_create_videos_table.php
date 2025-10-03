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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entite_id')->constrained('entites')->onDelete('cascade');
            $table->string('youtube_id', 255)->index();
            $table->string('titre', 500);
            $table->string('url', 500);
            $table->enum('status', ['NEW', 'PROCESSING', 'DONE'])->default('NEW');
            $table->string('thumbnails')->nullable();
            $table->timestamp('published_at')->nullable(); // Publication date
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->string('langue', 10)->nullable();
            $table->integer('step')->default(0);
            $table->string('type_contenu', 50)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
