<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('bill_cycle_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('deduction_type');                // Feed Cost, Advance, Loan EMI, Penalty, etc.
            $table->decimal('amount', 10, 2);
            $table->date('deduction_date');
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'applied'])->default('pending');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['farmer_id', 'bill_cycle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deductions');
    }
};
