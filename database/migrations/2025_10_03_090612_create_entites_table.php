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
        Schema::create('entites', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->enum('type', ['INDIVIDUEL', 'GROUPE']);
            $table->enum('type_contenu', ['TUTORIEL', 'RELIGION', 'EDUCATION', 'AUTRE'])->default('AUTRE');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entites');
    }
};
