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
        Schema::connection('firebird')->create('IMOBILIARIAS_SETUP', function (Blueprint $table) {
            $table->integer('ID')->primary();
            $table->integer('ID_IMOBILIARIA')->nullable();
            $table->double('TAXA')->nullable();
            $table->integer('ATIVO')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('IMOBILIARIAS_SETUP');
    }
};
