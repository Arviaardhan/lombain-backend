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
            $table->text('objectives')->nullable()->after('description');

            // Pastikan headline ada (jika sebelumnya belum ada)
            if (!Schema::hasColumn('teams', 'headline')) {
                $table->string('headline', 150)->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropColumn(['objectives', 'headline']);
            });
        });
    }
};
