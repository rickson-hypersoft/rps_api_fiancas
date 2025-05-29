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
        Schema::connection('firebird')->create('PROPOSTAS', function (Blueprint $table) {
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
            $table->double('Imovel_Aluguel')->nullable();
            $table->double('Imovel_Condominio')->nullable();
            $table->double('Imovel_Taxas')->nullable();
            $table->string('Imovel_CEP', 16)->nullable();
            $table->string('Imovel_Endereco', 100)->nullable();
            $table->string('Imovel_Bairro', 100)->nullable();
            $table->string('Imovel_Cidade', 100)->nullable();
            $table->string('Imovel_Estado', 50)->nullable();
            $table->string('Imovel_Numero', 20)->nullable();
            $table->string('Imovel_Complemento', 100)->nullable();
            $table->string('Imovel_Subtipo', 50)->nullable();
            $table->string('Imovel_Tag', 100)->nullable();
            $table->string('Imovel_Ramo_Atv', 100)->nullable();
            $table->double('Proposta_Total_Valor')->nullable();
            $table->integer('Proposta_Total_Parc')->nullable();
            $table->double('Proposta_Setup_Valor')->nullable();
            $table->integer('Proposta_Setup_Parc')->nullable();
            $table->string('Proposta_Tipo_Pagador', 100)->nullable();
            $table->string('Proposta_Status', 50)->nullable();
            $table->integer('Contrato_ID')->nullable();
            $table->string('Contrato_Status', 50)->nullable();

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
