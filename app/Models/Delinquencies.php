<?php

declare(strict_types = 1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Propostal\Propostal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->ID)) {
                $novoId    = DB::select("SELECT GEN_ID(GEN_INADIMPLENCIAS_ID, 1) AS ID FROM RDB\$DATABASE");
                $model->ID = $novoId[0]->ID;
            }
        });
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'ID_MOVI', 'ID')
            ->where('MOVI', 'inadimplencias');
    }

    public function histories()
    {
        return $this->hasMany(History::class, 'ID_MOVI', 'ID')
            ->where('MOVI', mb_convert_encoding('Inadimplência', 'ISO-8859-1'));
    }
}
