<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sentence_checks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $ownerId = DB::table('users')->where('email', 'rizky@sepuluh.app')->value('id');

        if ($ownerId) {
            DB::table('sentence_checks')->update(['user_id' => $ownerId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sentence_checks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
