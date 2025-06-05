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
        $fieldsToAdd = [
            'DATA_NASCIMENTO'         => 'DATE',
            'PROPOSTA_CREDITO_STATUS' => 'VARCHAR(50)',
            'OBSERVACAO'              => 'VARCHAR(2000)',
            'DATA_ULTIMA_ATUALIZACAO' => 'DATE',
            'HORA_ULTIMA_ATUALIZACAO' => 'TIME',
        ];

        foreach ($fieldsToAdd as $field => $type) {
            $exists = DB::connection('firebird')->selectOne("
                SELECT 1 FROM RDB\$RELATION_FIELDS
                WHERE RDB\$RELATION_NAME = 'PROPOSTAS' AND RDB\$FIELD_NAME = '$field'
            ");

            if (! $exists) {
                DB::connection('firebird')->statement("ALTER TABLE PROPOSTAS ADD $field $type");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $fields = [
            'DATA_NASCIMENTO',
            'PROPOSTA_CREDITO_STATUS',
            'OBSERVACAO',
            'DATA_ULTIMA_ATUALIZACAO',
            'HORA_ULTIMA_ATUALIZACAO',
        ];

        foreach ($fields as $field) {
            $exists = DB::connection('firebird')->selectOne("
                SELECT 1 FROM RDB\$RELATION_FIELDS
                WHERE RDB\$RELATION_NAME = 'PROPOSTAS' AND RDB\$FIELD_NAME = '$field'
            ");

            if ($exists) {
                DB::connection('firebird')->statement("ALTER TABLE PROPOSTAS DROP $field");
            }
        }
    }
};
