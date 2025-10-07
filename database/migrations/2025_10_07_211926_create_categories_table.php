<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('name', 100);
            $table->string('slug', 100);
            $table->text('description')->nullable();

            // Visual customization
            $table->string('color', 7)->default('#6366f1');
            $table->string('icon', 50)->nullable();

            // Display order
            $table->integer('order')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
