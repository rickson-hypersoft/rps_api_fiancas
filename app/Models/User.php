<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    protected $table = 'USUARIOS';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $guarded = [];

    public function getAuthPassword()
    {
        return $this->SENHA;
    }

    // public function getAuthIdentifierName()
    // {
    //     return "EMAIL";
    // }

    public function getJWTIdentifier()
    {
        return $this->getKey(); // geralmente o ID do usuário
    }

    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * @return HasOne<RealEstateSector, User>
     */
    public function realEstatesSector(): HasOne
    {
        return $this->hasOne(RealEstateSector::class, 'ID_IMOBILIARIA', 'ID');
    }
}
