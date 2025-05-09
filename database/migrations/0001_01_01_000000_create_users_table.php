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
        Schema::connection('firebird')->create('USUARIOS', function (Blueprint $table) {
            $table->integer('ID')->primary();
            $table->string('USUARIO', 30)->nullable();
            $table->string('SENHA', 255)->nullable();
            $table->string('NOME', 50)->nullable();
            $table->string('EMAIL', 150)->nullable();
            $table->string('CPF', 11)->nullable();
            $table->string('TELEFONE', 16)->nullable();
            $table->string('NIVEL', 50)->nullable();
            $table->string('CATEGORIA', 50)->nullable();
            $table->integer('ID_IMOBILIARIA')->nullable();
            $table->integer('ATIVO')->nullable()->default(1);
            $table->string('PERMISSOES', 2000)->nullable();
        });

        Schema::connection('firebird')->create('PASSWORD_RESET_TOKENS', function (Blueprint $table) {
            $table->string('EMAIL')->primary();
            $table->string('TOKEN');
            $table->timestamp('CREATED_AT')->nullable();
        });

        Schema::connection('firebird')->create('SESSIONS', function (Blueprint $table) {
            $table->string('ID')->primary();
            $table->foreignId('USER_ID')->nullable()->index();
            $table->string('IP_ADDRESS', 45)->nullable();
            $table->text('USER_AGENT')->nullable();
            $table->longText('PAYLOAD');
            $table->integer('LAST_ACTIVITY')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('USUARIOS');
        Schema::dropIfExists('PASSWORD_RESET_TOKENS');
        Schema::dropIfExists('SESSIONS');
    }
};
