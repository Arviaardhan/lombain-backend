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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->string('competition_name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->integer('max_members');
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->string('guidebook_url')->nullable();
            $table->date('deadline');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
