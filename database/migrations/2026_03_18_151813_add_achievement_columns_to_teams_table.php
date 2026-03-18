<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Cek apakah kolom 'status' ada untuk menentukan posisi 'after'
            if (Schema::hasColumn('teams', 'status')) {
                $table->string('rank')->nullable()->after('status');
            } else {
                // Jika tidak ada kolom status, buat rank tanpa 'after'
                $table->string('rank')->nullable();
            }

            $table->string('achievement_photo')->nullable()->after('rank');
            $table->text('reflection')->nullable()->after('achievement_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['rank', 'achievement_photo', 'reflection']);
        });
    }
};
