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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('spent', 15, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('period')->default('monthly');
            $table->string('type')->default('fixed');
            $table->decimal('percentage', 5, 2)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
            $table->index(['start_date', 'end_date']);
            $table->index(['user_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};

