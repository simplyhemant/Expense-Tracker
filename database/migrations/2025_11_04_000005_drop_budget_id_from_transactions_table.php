<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'budget_id')) {
                $table->dropConstrainedForeignId('budget_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'budget_id')) {
                $table->foreignId('budget_id')
                    ->nullable()
                    ->constrained('budgets')
                    ->nullOnDelete();
            }
        });
    }
};

