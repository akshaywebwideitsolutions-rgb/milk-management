<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('milk_collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone');
            $table->text('message');
            $table->string('status')->default('pending');    // pending, sent, failed
            $table->string('gateway_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
