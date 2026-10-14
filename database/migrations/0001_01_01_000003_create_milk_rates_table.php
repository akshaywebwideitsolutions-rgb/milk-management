<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('milk_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('milk_type', ['cow', 'buffalo', 'a2'])->unique();
            $table->decimal('rate_per_litre', 8, 2);        // e.g. 42.00
            $table->decimal('fat_min', 5, 2)->default(0);
            $table->decimal('fat_max', 5, 2)->default(0);
            $table->decimal('snf_min', 5, 2)->default(0);
            $table->decimal('snf_max', 5, 2)->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milk_rates');
    }
};
