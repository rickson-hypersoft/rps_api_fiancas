<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Extract extends Model
{
    protected $connection = 'firebird';

    protected $table = 'EXTRATO';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->ID)) {
                $novoId    = DB::select("SELECT GEN_ID(GEN_EXTRATO_ID, 1) AS ID FROM RDB\$DATABASE");
                $model->ID = $novoId[0]->ID;
            }
        });
    }
}
