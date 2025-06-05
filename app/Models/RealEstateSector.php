<?php

declare(strict_types = 1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class RealEstateSector extends Authenticatable
{
    protected $connection = 'firebird';

    protected $table = 'IMOBILIARIAS';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * @phpstan-return HasMany<RealEstateSectorSetup, RealEstateSector>
     */
    public function setups(): HasMany
    {
        return $this->hasMany(RealEstateSectorSetup::class, 'ID_IMOBILIARIA', 'ID');
    }
}
