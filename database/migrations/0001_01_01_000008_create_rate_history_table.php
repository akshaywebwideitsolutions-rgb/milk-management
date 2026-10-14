<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rate_history', function (Blueprint $table) {
            $table->id();
            $table->enum('milk_type', ['cow', 'buffalo', 'a2']);
            $table->decimal('old_rate', 8, 2);
            $table->decimal('new_rate', 8, 2);
            $table->foreignId('changed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_history');
    }
};
