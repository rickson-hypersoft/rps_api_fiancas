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
        Schema::connection('firebird')->create('ANEXOS', function (Blueprint $table): void {
            $table->integer('ID');
            $table->integer('ID_IMOBILIARIA');
            $table->integer('ID_MOVI')->nullable();
            $table->string('MOVI_SUB', 50)->nullable();
            $table->timestamp('DATA');
            $table->string('NOME_ARQUIVO', 100)->nullable();
            $table->string('DESCRICAO', 100)->nullable();

            $table->primary(['ID', 'ID_IMOBILIARIA']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->dropIfExists('ANEXOS');
    }
};
