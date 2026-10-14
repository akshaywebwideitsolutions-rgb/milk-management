<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('milk_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->date('collection_date');
            $table->enum('session', ['morning', 'evening']);
            $table->enum('milk_type', ['cow', 'buffalo', 'a2']);
            $table->decimal('fat_percent', 5, 2)->default(0);
            $table->decimal('snf_percent', 5, 2)->default(0);
            $table->decimal('quantity', 8, 2);               // litres
            $table->decimal('rate_per_litre', 8, 2);
            $table->decimal('amount', 10, 2);                // quantity * rate
            $table->boolean('sms_sent')->default(false);
            $table->string('sms_status')->nullable();
           // $table->foreignId('recorded_by')->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by')
      ->nullable()
      ->constrained('users')
      ->nullOnDelete();
            $table->timestamps();

            $table->index(['collection_date', 'session']);
            $table->index(['farmer_id', 'collection_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milk_collections');
    }
};
