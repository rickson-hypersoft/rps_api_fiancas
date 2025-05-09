<?php

declare(strict_types = 1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $connection = 'firebird';

    protected $table = 'usuarios';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function getAuthPassword()
    {
        return $this->senha;
    }

    // Retorna o identificador que será armazenado no token JWT
    public function getJWTIdentifier()
    {
        return $this->getKey(); // geralmente o ID do usuário
    }

    // Retorna um array com claims personalizados, se quiser
    public function getJWTCustomClaims()
    {
        return []; // ou adicione claims aqui
    }
}
