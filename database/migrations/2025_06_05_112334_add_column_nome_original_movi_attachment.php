<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verifica se a coluna MOVI já existe na tabela ANEXOS
        $columnExists = DB::connection('firebird')->selectOne("
            SELECT 1 FROM RDB\$RELATION_FIELDS
            WHERE RDB\$RELATION_NAME = 'ANEXOS' AND RDB\$FIELD_NAME = 'MOVI'
        ");

        // Se não existir, adiciona
        if (! $columnExists) {
            DB::connection('firebird')->statement('ALTER TABLE ANEXOS ADD MOVI VARCHAR(50)');
        }

        // Mesmo para NOME_ARQUIVO_ORIGINAL
        $column2Exists = DB::connection('firebird')->selectOne("
            SELECT 1 FROM RDB\$RELATION_FIELDS
            WHERE RDB\$RELATION_NAME = 'ANEXOS' AND RDB\$FIELD_NAME = 'NOME_ARQUIVO_ORIGINAL'
        ");

        if (! $column2Exists) {
            DB::connection('firebird')->statement('ALTER TABLE ANEXOS ADD NOME_ARQUIVO_ORIGINAL VARCHAR(100)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mesmo processo para drop com segurança
        $columnExists = DB::connection('firebird')->selectOne("
            SELECT 1 FROM RDB\$RELATION_FIELDS
            WHERE RDB\$RELATION_NAME = 'ANEXOS' AND RDB\$FIELD_NAME = 'MOVI'
        ");

        if ($columnExists) {
            DB::connection('firebird')->statement('ALTER TABLE ANEXOS DROP MOVI');
        }

        $column2Exists = DB::connection('firebird')->selectOne("
            SELECT 1 FROM RDB\$RELATION_FIELDS
            WHERE RDB\$RELATION_NAME = 'ANEXOS' AND RDB\$FIELD_NAME = 'NOME_ARQUIVO_ORIGINAL'
        ");

        if ($column2Exists) {
            DB::connection('firebird')->statement('ALTER TABLE ANEXOS DROP NOME_ARQUIVO_ORIGINAL');
        }
    }
};
