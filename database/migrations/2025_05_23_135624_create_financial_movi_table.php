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
        Schema::connection('firebird')->create('FINANCEIRO_MOVI', function (Blueprint $table): void {
            $table->integer('ID');
            $table->integer('ID_IMOBILIARIA');
            $table->integer('ID_CONTA')->nullable();
            $table->integer('ID_CATEGORIA')->nullable();
            $table->date('DATA')->nullable();
            $table->string('HISTORICO', 100)->nullable();
            $table->string('TIPO', 1)->nullable();

            $table->primary(['ID', 'ID_IMOBILIARIA']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->dropIfExists('FINANCEIRO_MOVI');
    }
};
