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
        Schema::connection('firebird')->create('empresas', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('razao', 100)->nullable();
            $table->string('fantasia', 100)->nullable();
            $table->string('cnpj', 14)->nullable();
            $table->string('endereco', 100)->nullable();
            $table->string('numero', 30)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('telefone', 16)->nullable();
            $table->string('contato', 100)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->string('representante', 100)->nullable();
            $table->string('email', 150)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->dropIfExists('empresas');
    }
};
