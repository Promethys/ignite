<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();

            // Goal type
            $table->enum('type', ['simple', 'quantifiable', 'recurring', 'multi_step'])->default('simple');

            // Values ​​for quantifiable goals
            $table->decimal('target_value', 10, 2)->nullable();
            $table->decimal('current_value', 10, 2)->default(0);
            $table->string('unit', 50)->nullable();

            // Recurrence
            $table->enum('recurrence', ['daily', 'weekly', 'monthly', 'annually'])->nullable();

            // Dates
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Status and priority
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'paused', 'abandoned'])->default('not_started');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            // Gamification
            $table->integer('points')->default(0);

            // Visibility
            $table->boolean('is_public')->default(false);

            // Display order
            $table->integer('order')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
