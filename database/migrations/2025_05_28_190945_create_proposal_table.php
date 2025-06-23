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
        Schema::connection('firebird')->create('PROPOSTAS', function (Blueprint $table): void {
            $table->integer('ID');
            $table->integer('ID_IMOBILIARIA');
            $table->date('DATA')->nullable();
            $table->time('HORA')->nullable();
            $table->string('PESSOA_TIPO', 2)->nullable();
            $table->string('PESSOA_DOC', 14)->nullable();
            $table->string('PESSOA_NOME', 100)->nullable();
            $table->string('PESSOA_FANTASIA', 100)->nullable();
            $table->string('PESSOA_TRIBUTACAO', 50)->nullable();
            $table->string('PESSOA_PJ_TIPO', 50)->nullable();
            $table->string('PESSOA_EMAIL', 100)->nullable();
            $table->string('PESSOA_TELEFONE', 16)->nullable();
            $table->string('PESSOA_CEP', 16)->nullable();
            $table->string('PESSOA_ENDERECO', 100)->nullable();
            $table->string('PESSOA_BAIRRO', 100)->nullable();
            $table->string('PESSOA_CIDADE', 100)->nullable();
            $table->string('PESSOA_ESTADO', 50)->nullable();
            $table->string('PESSOA_NUMERO', 20)->nullable();
            $table->string('IMOVEL_TIPO', 1)->nullable();
            $table->double('IMOVEL_ALUGUEL')->nullable();
            $table->double('IMOVEL_CONDOMINIO')->nullable();
            $table->double('IMOVEL_TAXAS')->nullable();
            $table->string('IMOVEL_CEP', 16)->nullable();
            $table->string('IMOVEL_ENDERECO', 100)->nullable();
            $table->string('IMOVEL_BAIRRO', 100)->nullable();
            $table->string('IMOVEL_CIDADE', 100)->nullable();
            $table->string('IMOVEL_ESTADO', 50)->nullable();
            $table->string('IMOVEL_NUMERO', 20)->nullable();
            $table->string('IMOVEL_COMPLEMENTO', 100)->nullable();
            $table->string('IMOVEL_SUBTIPO', 50)->nullable();
            $table->string('IMOVEL_TAG', 100)->nullable();
            $table->string('IMOVEL_RAMO_ATV', 100)->nullable();
            $table->double('PROPOSTA_TOTAL_VALOR')->nullable();
            $table->integer('PROPOSTA_TOTAL_PARC')->nullable();
            $table->double('PROPOSTA_SETUP_VALOR')->nullable();
            $table->integer('PROPOSTA_SETUP_PARC')->nullable();
            $table->string('PROPOSTA_TIPO_PAGADOR', 100)->nullable();
            $table->string('PROPOSTA_STATUS', 50)->nullable();
            $table->integer('CONTRATO_ID')->nullable();
            $table->string('CONTRATO_STATUS', 50)->nullable();

            $table->primary(['ID', 'ID_IMOBILIARIA']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->dropIfExists('PROPOSTAS');
    }
};
