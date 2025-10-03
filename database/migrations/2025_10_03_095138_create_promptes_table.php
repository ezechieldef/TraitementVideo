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
        Schema::create('promptes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entite_id')->nullable()->constrained('entites')->onDelete('cascade');
            $table->enum('type', ['SECTION', 'RESUME']);
            $table->string('categorie', 100)->nullable();
            $table->string('titre');
            $table->longText('contenu');
            $table->string('langue', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('visible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promptes');
    }
};
