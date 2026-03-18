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
        Schema::table('team_user', function (Blueprint $table) {
            // WAJIB: Bungkus 'status' dengan pengecekan agar tidak Duplicate Column error
            if (!Schema::hasColumn('team_user', 'status')) {
                $table->string('status')->default('pending')->after('user_id');
            }

            // Tambahkan kolom role
            if (!Schema::hasColumn('team_user', 'role')) {
                $table->string('role')->nullable()->after('status');
            }

            // Tambahkan role_id
            if (!Schema::hasColumn('team_user', 'role_id')) {
                $table->foreignId('role_id')->nullable()->constrained('team_roles')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_user', function (Blueprint $table) {
            //
        });
    }
};
