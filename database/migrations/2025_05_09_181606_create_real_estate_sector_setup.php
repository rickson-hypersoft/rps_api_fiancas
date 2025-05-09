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
        Schema::connection('firebird')->create('imobiliarias_setup', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('id_imobiliaria')->nullable();
            $table->double('taxa')->nullable();
            $table->integer('ativo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->dropIfExists('imobiliarias_setup');
    }
};
