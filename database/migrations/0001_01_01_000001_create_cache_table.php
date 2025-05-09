<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('firebird')->create('CACHE', function (Blueprint $table) {
            $table->string('KEY')->primary();
            $table->mediumText('VALUE');
            $table->integer('EXPIRATION');
        });

        Schema::connection('firebird')->create('CACHE_LOCKS', function (Blueprint $table) {
            $table->string('KEY')->primary();
            $table->string('OWNER');
            $table->integer('EXPIRATION');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CACHE');
        Schema::dropIfExists('CACHE_LOCKS');
    }
};
