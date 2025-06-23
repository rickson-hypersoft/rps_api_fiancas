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
        Schema::connection('firebird')->create('PROPOSTAS_PAGAMENTOS', function (Blueprint $table): void {
            $table->integer('ID');
            $table->integer('ID_IMOBILIARIA');
            $table->integer('ID_MOVI');
            $table->string('ID_USUARIO_INTEGRACAO', 100);
            $table->string('METODO_PAGAMENTO', '50')->nullable();
            $table->float('VALOR')->nullable();
            $table->string('STATUS', '100')->nullable();
            $table->integer('ID_USUARIO');
            $table->date('DATA')->nullable();
            $table->time('HORA')->nullable();

            $table->primary(['ID', 'ID_IMOBILIARIA']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->dropIfExists('PROPOSTAS_PAGAMENTOS');
    }
};
