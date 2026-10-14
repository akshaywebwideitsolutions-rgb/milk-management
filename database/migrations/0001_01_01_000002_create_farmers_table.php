<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_id')->unique();           // F-001
            $table->string('name');
            $table->string('phone', 15)->unique();
            $table->string('village')->nullable();
            $table->enum('milk_type', ['cow', 'buffalo', 'a2'])->default('cow');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
