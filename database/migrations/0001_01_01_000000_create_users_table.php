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
        Schema::connection('firebird')->create('usuarios', function (Blueprint $table) {
            $table->integer('id')->primary(); // Evita uso de auto-incremento
            $table->string('usuario', 30)->nullable();
            $table->string('senha', 255)->nullable();
            $table->string('nome', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('cpf', 11)->nullable();
            $table->string('telefone', 16)->nullable();
            $table->string('nivel', 50)->nullable();
            $table->string('categoria', 50)->nullable();
            $table->integer('id_imobiliaria')->nullable();
            $table->integer('ativo')->default(1)->nullable();
            $table->string('permissoes', 2000)->nullable();
        });

        Schema::connection('firebird')->create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::connection('firebird')->create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->dropIfExists('usuarios');
        Schema::connection('firebird')->dropIfExists('password_reset_tokens');
        Schema::connection('firebird')->dropIfExists('sessions');
    }
};
