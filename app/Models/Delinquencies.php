<?php

declare(strict_types = 1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Propostal\Propostal;
use Illuminate\Database\Eloquent\Model;

class Delinquencies extends Model
{
    protected $connection = 'firebird';

    protected $table = 'INADIMPLENCIAS';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function propostal()
    {
        return $this->belongsTo(Propostal::class, 'CONTRATO_ID', 'ID');
    }
}
