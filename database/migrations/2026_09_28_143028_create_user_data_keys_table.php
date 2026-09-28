<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('user-data.key_connection');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_data_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('wrapped_key');
            $table->string('master_key_id', 16)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_data_keys');
    }
};
