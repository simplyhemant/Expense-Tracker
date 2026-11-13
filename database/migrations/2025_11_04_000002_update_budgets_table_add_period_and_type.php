<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            if (!Schema::hasColumn('budgets', 'period')) {
                $table->string('period')->default('monthly')->after('end_date');
            }

            if (!Schema::hasColumn('budgets', 'type')) {
                $table->string('type')->default('fixed')->after('period');
            }

            if (!Schema::hasColumn('budgets', 'percentage')) {
                $table->decimal('percentage', 5, 2)->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            if (Schema::hasColumn('budgets', 'percentage')) {
                $table->dropColumn('percentage');
            }
            if (Schema::hasColumn('budgets', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('budgets', 'period')) {
                $table->dropColumn('period');
            }
        });
    }
};

