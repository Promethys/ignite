<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->onDelete('cascade');

            // Milestone Information
            $table->string('title');
            $table->text('description')->nullable();

            // Target value
            $table->decimal('target_value', 10, 2)->nullable();

            // Order
            $table->integer('order')->default(0);

            // Status
            $table->timestamp('completed_at')->nullable();

            // Reward
            $table->integer('points_reward')->nullable()->default(0);

            $table->timestamps();

            $table->index(['goal_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
