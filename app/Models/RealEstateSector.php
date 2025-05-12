<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function setup()
    {
        return $this->hasOne(RealEstateSectorSetup::class, 'ID_IMOBILIARIA', 'ID');
    }
}
