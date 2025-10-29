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
        Schema::create('freight_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freight_table_id')->constrained('freight_tables')->cascadeOnDelete();
            $table->unsignedInteger('row_number')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['freight_table_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freight_import_logs');
    }
};
