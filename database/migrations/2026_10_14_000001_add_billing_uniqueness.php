<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoices' => ['farmer_id', 'bill_cycle_id']] as $table => $columns) {
            if (DB::table($table)->select($columns)->groupBy($columns)->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException("Duplicate records exist in {$table}. Reconcile them before migrating; no records have been deleted.");
            }
        }
        Schema::table('invoices', fn (Blueprint $table) => $table->unique(['farmer_id', 'bill_cycle_id'], 'invoices_farmer_cycle_unique'));
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->dropUnique('invoices_farmer_cycle_unique'));
    }
};
