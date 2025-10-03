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
        Schema::create('resume_exports', function (Blueprint $table) {
            $table->id();
            $table->enum('format', ['TEXT', 'HTML', 'MARKDOWN', 'PDF']);
            $table->enum('cible', ['WORDPRESS', 'MEDIUM', 'API', 'FILE']);
            $table->text('url_cible')->nullable();
            $table->text('api_key')->nullable();
            $table->enum('statut', ['SENT', 'FAILED'])->default('SENT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resume_exports');
    }
};
