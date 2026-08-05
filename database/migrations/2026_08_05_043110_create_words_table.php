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
        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->string('en');
            $table->string('ipa')->nullable();
            $table->string('pos')->nullable();
            $table->string('translation');
            $table->text('example')->nullable();
            $table->text('example_translation')->nullable();
            $table->string('verb1')->nullable();
            $table->string('verb2')->nullable();
            $table->string('verb3')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('words');
    }
};
