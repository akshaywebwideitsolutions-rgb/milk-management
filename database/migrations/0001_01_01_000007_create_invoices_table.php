<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();          // INV-2401
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bill_cycle_id')->constrained();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_litres', 10, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'pending', 'sent', 'paid'])->default('draft');
            $table->date('sent_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            $table->timestamps();

            $table->index(['bill_cycle_id', 'status']);
            $table->index(['farmer_id', 'bill_cycle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
