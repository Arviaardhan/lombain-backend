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
            $table->foreignId('role_id')
                ->nullable()
                ->constrained('team_roles')
                ->cascadeOnDelete();

            $table->enum('status', ['pending', 'accepted', 'rejected'])
                ->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
