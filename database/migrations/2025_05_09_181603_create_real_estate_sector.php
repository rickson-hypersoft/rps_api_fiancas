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
        Schema::connection('firebird')->create('IMOBILIARIAS', function (Blueprint $table) {
            $table->integer('ID')->primary();
            $table->string('RAZAO', 100)->nullable();
            $table->string('FANTASIA', 100)->nullable();
            $table->string('CNPJ', 14)->nullable();
            $table->string('ENDERECO', 100)->nullable();
            $table->string('NUMERO', 30)->nullable();
            $table->string('BAIRRO', 100)->nullable();
            $table->string('CIDADE', 100)->nullable();
            $table->string('UF', 2)->nullable();
            $table->string('CEP', 10)->nullable();
            $table->string('COMPLEMENTO', 100)->nullable();
            $table->string('TELEFONE', 16)->nullable();
            $table->string('CONTATO', 100)->nullable();
            $table->string('CARGO', 100)->nullable();
            $table->string('REPRESENTANTE', 100)->nullable();
            $table->string('EMAIL', 150)->nullable();
            $table->string('TIPO_PAGAMENTO', 30)->nullable();
            $table->double('TAXA_PADRAO')->nullable();
            $table->double('CUSTO_SAIDA')->nullable();
            $table->double('COBERTURA_TOTAL')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('IMOBILIARIAS');
    }
};
