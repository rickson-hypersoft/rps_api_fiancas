<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreResponse extends Model
{
    protected $connection = 'firebird';

    protected $table = 'ASSERTIVA_SCORE_RETORNO';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
