<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('milk_rates', function (Blueprint $table) {
            $table->dropUnique('milk_rates_milk_type_unique');
            $table->index(['milk_type', 'fat_min', 'fat_max', 'snf_min', 'snf_max'], 'milk_rates_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('milk_rates', function (Blueprint $table) {
            $table->dropIndex('milk_rates_lookup_index');
            $table->unique('milk_type');
        });
    }
};
