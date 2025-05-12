<?php

declare(strict_types = 1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class RealEstateSectorSetup extends Authenticatable
{
    protected $connection = 'firebird';

    protected $table = 'IMOBILIARIAS_SETUP';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
