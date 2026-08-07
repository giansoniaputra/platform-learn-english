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
        Schema::table('sentence_checks', function (Blueprint $table) {
            $table->text('translation')->nullable()->after('output_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sentence_checks', function (Blueprint $table) {
            $table->dropColumn('translation');
        });
    }
};
