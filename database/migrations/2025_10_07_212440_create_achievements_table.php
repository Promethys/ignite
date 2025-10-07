<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();

            // Basic information
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description');
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();

            // Visual
            $table->string('icon', 50);
            $table->string('badge_image')->nullable();

            // Type and criteria
            $table->enum('type', ['goal_completion', 'streak', 'points', 'consistency', 'special']);
            $table->json('criteria');

            // Reward
            $table->integer('points_reward')->default(0);

            // Rarity
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');

            // Display order
            $table->integer('order')->default(0);

            // On/off
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
