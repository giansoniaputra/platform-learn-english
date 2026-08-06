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
        Schema::table('keywords', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $ownerId = DB::table('users')->where('email', 'rizky@sepuluh.app')->value('id');

        if ($ownerId) {
            DB::table('keywords')->update(['user_id' => $ownerId]);
        }

        Schema::table('keywords', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['user_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('slug');
        });
    }
};
