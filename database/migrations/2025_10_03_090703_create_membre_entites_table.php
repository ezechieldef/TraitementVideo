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
        Schema::create('membre_entites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entite_id')->constrained('entites')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('invited_by')->nullable()->constrained('users')->cascadeOnDelete();

            $table->enum('invite_status', ['INVITED', 'ACCEPTED', 'REJECTED'])->default('ACCEPTED'); //
            $table->enum('role', ['OWNER', 'MEMBER'])->default('MEMBER');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membre_entites');
    }
};
