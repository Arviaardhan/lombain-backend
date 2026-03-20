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
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->onDelete('cascade');
                $table->string('role_name');
                $table->integer('max_slot')->default(1);
                $table->timestamps();
            });
        }

        // 2. Tambahkan kolom role_id ke team_user jika belum ada
        Schema::table('team_user', function (Blueprint $table) {
            if (!Schema::hasColumn('team_user', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('team_id')->constrained('roles')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
        Schema::dropIfExists('roles');
    }
};
