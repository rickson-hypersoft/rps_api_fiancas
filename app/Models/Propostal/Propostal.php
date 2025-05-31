<?php

declare(strict_types=1);

namespace App\Models\Propostal;

use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class Propostal extends Authenticatable
{
    protected $connection = 'firebird';

    protected $table = 'PROPOSTAS';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ID)) {
                $novoId = DB::select("SELECT GEN_ID(GEN_PROPOSTAS_ID, 1) AS ID FROM RDB\$DATABASE");
                $model->ID = $novoId[0]->ID;
            }
        });
    }
}