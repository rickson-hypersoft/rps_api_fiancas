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
        Schema::connection('firebird')->table('PROPOSTAS', function (Blueprint $table): void {
            $table->string('ID_USUARIO_INTEGRACAO', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->table('PROPOSTAS', function (Blueprint $table): void {
            $table->dropColumn('ID_USUARIO_INTEGRACAO');
        });
    }
};
