<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            if (! Schema::hasColumn('resumes', 'is_processing')) {
                $table->boolean('is_processing')->default(false)->after('isExported');
            }
            if (! Schema::hasColumn('resumes', 'error_message')) {
                $table->text('error_message')->nullable()->after('is_processing');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            if (Schema::hasColumn('resumes', 'error_message')) {
                $table->dropColumn('error_message');
            }
            if (Schema::hasColumn('resumes', 'is_processing')) {
                $table->dropColumn('is_processing');
            }
        });
    }
};
