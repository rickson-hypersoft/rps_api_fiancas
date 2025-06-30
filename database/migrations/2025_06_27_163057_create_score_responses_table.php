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
        Schema::connection('firebird')->create('ASSERTIVA_SCORE_RETORNO', function (Blueprint $table): void {
            $table->integer('ID');

            $table->string('PESSOA_DOC');
            $table->date('DATA')->nullable();
            $table->time('HORA')->nullable();
            $table->string('PRODUTO')->nullable();
            $table->string('FUNCIONALIDADE')->nullable();
            $table->string('PROTOCOLO')->nullable();
            $table->string('SCORE_CLASSE')->nullable();
            $table->string('SCORE_FAIXA_TITULO')->nullable();
            $table->string('SCORE_FAIXA_DESCRICAO')->nullable();
            $table->string('SCORE_PONTOS')->nullable();
            $table->decimal('RENDA_PRESUMIDA')->nullable();
            $table->date('EXPIRA_EM')->nullable();

            $table->primary(['ID']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->table('ASSERTIVA_SCORE_RETORNO', function (Blueprint $table): void {
            $table->dropIfExists();
        });
    }
};
