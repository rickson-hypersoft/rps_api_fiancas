<?php

declare(strict_types = 1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $connection = 'firebird';

    protected $table = 'USUARIOS';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function getAuthPassword()
    {
        return $this->SENHA;
    }

    public function getAuthIdentifierName()
    {
        return "EMAIL";
    }

    public function getJWTIdentifier()
    {
        return $this->getKey(); // geralmente o ID do usuário
    }

    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
