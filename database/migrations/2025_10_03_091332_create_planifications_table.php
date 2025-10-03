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
        Schema::create('planifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entite_id')->constrained('entites')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('chaine_id')->constrained('chaines')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('type', ['UNIQUE', 'JOURNALIER', 'HEBDOMADAIRE', 'MENSUEL'])->default('UNIQUE');
            $table->integer('repeterChaque')->nullable(); // nombre de jours JOUR=>1, SEMAINE=>7, MOIS=>30
            $table->timestamp('next_execution_at')->nullable();
            $table->timestamp('last_execution_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planifications');
    }
};
