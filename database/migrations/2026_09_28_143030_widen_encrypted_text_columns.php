<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->text('title')->change();
            $table->text('unit')->nullable()->change();
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->text('title')->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->text('name')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->string('title')->change();
            $table->string('unit', 50)->nullable()->change();
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->string('title')->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('name', 100)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }
};
