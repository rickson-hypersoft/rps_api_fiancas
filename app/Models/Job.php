<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    // Define o nome da tabela no banco de dados.
    // Para o Firebird, é comum que a tabela seja criada em maiúsculas se não houver aspas no DDL.
    protected $table = 'JOBS'; // Ou 'jobs' se o seu driver Firebird já estiver tratando isso.

    // MUITO IMPORTANTE: Diz ao Laravel que a chave primária NÃO é auto-incrementável (no sentido de PDO lastInsertId).
    // Isso significa que o banco de dados (via trigger/generator) é responsável por gerar o ID.
    public $incrementing = false;

    // O tipo da chave primária. Embora seja numérica, as vezes definir como 'string'
    // para tipos de ID não auto-incrementáveis via lastInsertId() pode ajudar.
    // Se seu ID for numérico e está sendo gerado corretamente pelo Firebird, pode testar com 'int' ou sem esta linha.
    protected $keyType = 'string'; // Ou 'int' se o ID no seu Firebird for um INTEGER/BIGINT

    // Define a coluna da chave primária se não for 'id'
    protected $primaryKey = 'id'; // Se a sua coluna ID no Firebird for 'ID' maiúsculo

    // Laravel espera 'created_at', mas a tabela jobs usa 'created_at' como inteiro
    // e não tem 'updated_at' por padrão.
    public const CREATED_AT = 'create_at'; // Se a coluna for MAIÚSCULA
    public const UPDATED_AT = null; // A tabela jobs não tem updated_at

    // Define os casts para garantir que os valores sejam interpretados corretamente.
    protected $casts = [
        'payload'      => 'array', // O payload é um JSON, então um array PHP.
        'attempts'     => 'integer',
        'reserved_at'  => 'integer',
        'available_at' => 'integer',
        'created_at'   => 'integer',
    ];
}
