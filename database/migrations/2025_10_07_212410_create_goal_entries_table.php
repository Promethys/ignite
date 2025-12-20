<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->onDelete('cascade');

            // Saved value
            $table->decimal('value', 10, 2);
            $table->decimal('previous_value', 10, 2)->default(0);

            // Notes and context
            $table->text('note')->nullable();

            // Date of entry
            $table->date('entry_date');

            // Attached files
            $table->string('attachment_path')->nullable();
            $table->string('attachment_type', 50)->nullable();

            $table->timestamps();

            $table->index(['goal_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_entries');
    }
};
