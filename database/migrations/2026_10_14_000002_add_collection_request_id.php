<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milk_collections', fn (Blueprint $table) => $table->uuid('request_id')->nullable()->unique());
    }

    public function down(): void
    {
        Schema::table('milk_collections', function (Blueprint $table) {
            $table->dropUnique(['request_id']);
            $table->dropColumn('request_id');
        });
    }
};
