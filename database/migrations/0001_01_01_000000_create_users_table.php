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
        Schema::connection('firebird')->create('USUARIOS', function (Blueprint $table): void {
            $table->integer('ID')->primary(); // Evita uso de auto-incremento
            $table->string('USUARIO', 30)->nullable();
            $table->string('SENHA', 255)->nullable();
            $table->string('NOME', 50)->nullable();
            $table->string('EMAIL', 150)->nullable();
            $table->string('CPF', 11)->nullable();
            $table->string('TELEFONE', 16)->nullable();
            $table->string('NIVEL', 50)->nullable();
            $table->string('CATEGORIA', 50)->nullable();
            $table->integer('ID_IMOBILIARIA')->nullable();
            $table->integer('ATIVO')->default(1)->nullable();
            $table->string('PERMISSOES', 2000)->nullable();
        });

        Schema::connection('firebird')->create('PASSWORD_RESET_TOKENS', function (Blueprint $table): void {
            $table->string('EMAIL')->primary();
            $table->string('TOKEN');
            $table->timestamp('CREATED_AT')->nullable();
        });

        Schema::connection('firebird')->create('SESSIONS', function (Blueprint $table): void {
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
        Schema::connection('firebird')->dropIfExists('USUARIOS');
        Schema::connection('firebird')->dropIfExists('PASSWORD_RESET_TOKENS');
        Schema::connection('firebird')->dropIfExists('SESSIONS');
    }
};
