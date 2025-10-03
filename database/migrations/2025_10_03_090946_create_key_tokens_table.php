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
        Schema::create('key_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entite_id')->constrained('entites')->onDelete('cascade');
            $table->enum('type', ['YOUTUBE', 'LLM']); // youtube=>youtube api key
            $table->foreignId('llm_id')->nullable()->constrained('l_l_m_s')->onDelete('set null');
            $table->text('value');
            $table->enum('status', ['WORKING', 'DISABLED', 'NOT_WORKING'])->default('NOT_WORKING');
            $table->integer('usage_limit_count')->nullable();
            $table->integer('limit_periode_minutes')->nullable();
            $table->boolean('isLimitExceded')->default(false);
            $table->timestamp('limitExceedAt')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('quota_used')->default(0);
            $table->integer('priority')->default(1);
            $table->integer('error_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('key_tokens');
    }
};
