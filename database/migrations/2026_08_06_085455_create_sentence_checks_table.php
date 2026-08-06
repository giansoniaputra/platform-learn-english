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
        Schema::create('sentence_checks', function (Blueprint $table) {
            $table->id();
            $table->string('input_hash', 64)->unique();
            $table->text('input_text');
            $table->string('input_language', 2);
            $table->text('output_en');
            $table->boolean('is_correct')->nullable();
            $table->text('explanation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sentence_checks');
    }
};
