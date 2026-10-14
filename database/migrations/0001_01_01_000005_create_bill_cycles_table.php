<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bill_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // "Jul 2026 - 1st Half"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('cycle_type', ['10_days', '15_days', 'custom'])->default('15_days');
            $table->boolean('auto_generate_invoice')->default(true);
            $table->boolean('sms_on_invoice')->default(true);
            $table->enum('status', ['active', 'completed', 'draft'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_cycles');
    }
};
