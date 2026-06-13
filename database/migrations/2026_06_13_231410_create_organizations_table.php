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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('business_id', 20)->unique();
            $table->string('name');
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->integer('reviews_count')->nullable();
            $table->integer('ratings_count')->nullable();
            $table->enum('status', ['pending', 'parsing', 'done', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('parsed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
