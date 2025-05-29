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
        Schema::connection('firebird')->create('FINANCEIRO_CONTAS', function (Blueprint $table) {
            $table->integer('ID');
            $table->integer('ID_IMOBILIARIA');

            $table->string('TIPO_CONTA', 50)->nullable();
            $table->string('DESCRICAO', 100)->nullable();
            $table->string('BANCO_TITULAR', 100)->nullable();
            $table->string('BANCO_CNPJ', 100)->nullable();
            $table->string('BANCO', 3)->nullable();
            $table->string('BANCO_AGENCIA', 20)->nullable();
            $table->string('BANCO_CONTA', 20)->nullable();
            $table->string('BANCO_FINALIDADE', 100)->nullable();
            $table->string('BANCO_PIX', 100)->nullable();
            $table->integer('ATIVO')->default(1)->nullable();

            $table->primary(['ID', 'ID_IMOBILIARIA']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->dropIfExists('FINANCEIRO_CONTAS');
    }
};
